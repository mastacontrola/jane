#!/bin/bash
dots() {
    local pad=$(printf "%0.1s" "."{1..60})
    printf " * %s%*.*s" "$1" 0 $((60-${#1})) "$pad"
    return 0
}
banner() {

clear
echo
echo
echo "        888888                            ";
echo "          \"88b                            ";
echo "           888                            ";
echo "           888  8888b.  88888b.   .d88b.  ";
echo "           888     \"88b 888 \"88b d8P  Y8b ";
echo "           888 .d888888 888  888 88888888 ";
echo "           88P 888  888 888  888 Y8b.     ";
echo "           888 \"Y888888 888  888  \"Y8888  ";
echo "         .d88P                            ";
echo "       .d88P\"                             ";
echo "      888P\"                               ";
echo
echo
echo "   A free and open account provisioning solution."
echo
echo "   Version $janeVersion"
echo
echo
}
updateSchema() {
    dots "Checking for schema updates"

    local getOldSchemaVersion="SELECT settingValue FROM globalSettings WHERE settingKey = 'schemaVersion'"
    local options="-sN"

    if [[ $mysqlHost != "" ]]; then
        local options="$options -h$mysqlHost"
    fi
    if [[ $mysqlUser != "" ]]; then
        local options="$options -u$mysqlUser"
    fi
    if [[ $mysqlPass != "" ]]; then
        local options="$options -p$mysqlPass"
    fi
    local options="$options -D jane -e"

    local oldSchemaVersion=$(mysql $options "$getOldSchemaVersion") > /dev/null 2>&1

    if [[ "$currentSchemaVersion" -gt "$oldSchemaVersion" ]]; then
        echo "Needed"

        local COUNTER=$oldSchemaVersion
        while [[ "$COUNTER" -lt "$currentSchemaVersion" ]]; do
            schema${COUNTER}
            let COUNTER=COUNTER+1 
        done

    else
        echo "Not needed"
    fi

}
updateServer() {
    dots "Updating system, this could take a while"
    local useYum=$(command -v yum)
    local useDnf=$(command -v dnf)
    if [[ -e "$useDnf" ]]; then
        dnf update -y > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Updated" || echo "Failed"
    elif [[ -e "$useYum" ]]; then
        yum update -y > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Updated" || echo "Failed"
    else
        echo "Failed"
        return 1
    fi
}
checkOS() {
    local OS=$(uname -s)
    if [[ $OS =~ ^[^Ll][^Ii][^Nn][^Uu][^Xx] ]] ; then
        echo "We do not currently support installation on non-linux operating systems"
        exit 2 # Fail OS Check
    fi
    dots "Checking for compatible OS"
    if [[ -e /etc/os-release ]] ; then
        source "/etc/os-release"
        ID=$(echo $ID | awk '{print tolower($0)}')
        case $ID in
            centos|rhel|fedora)
                echo "$ID"
                ;;
            *)
                echo "$ID is incompatible"
                exit 2
                ;;
        esac
    elif [[ -e /etc/redhat-release ]] ; then
        local ID=$(cat /etc/redhat-release | awk '{print tolower($1)}');
        echo "$ID"
    else
        echo "Could not determine OS"
        exit 2
    fi
}
installRemiAndEpel() {
    installer=$(command -v dnf)
    if [[ -z $installer ]] ; then
        installer=$(command -v yum)
        if [[ -z $installer ]] ; then
            echo " * Cannot find an appropriate installer"
            exit 3
        fi
        local yum="1"
        checkOrInstallPackage "yum-utils" "1"
    fi
    dots "Ensuring Remi and Epel repos are installed"
    case $ID in
        fedora)
            repo="fedora"
            ;;
        rhel)
            repo="enterprise"
            [[ $yum -eq 1 ]] && subscription-manager repos --enable=rhel-${VERSION_ID}-server-optional-rpms > /dev/null 2>&1
            ;;
        *)
            repo="enterprise"
            $installer install https://dl.fedoraproject.org/pub/epel/epel-release-latest-${VERSION_ID}.noarch.rpm -y > /dev/null 2>&1
            ;;
    esac
    $installer install http://rpms.remirepo.net/fedora/remi-release-${VERSION_ID}.rpm -y > /dev/null 2>&1
    if [[ $yum -eq 1 ]] ;then
        checkOrInstallPackage "yum-utils" "1"
        yum-config-manager --enable remi-php70 > /dev/null 2>&1
    else
        $installer config-manager --set-enabled remi-php70 > /dev/null 2>&1
    fi
    [[ $? -eq 0 ]] && echo "Installed" || echo "Failed"
}
checkOrInstallPackage() {
    local package="$1"
    local silent="$2"
    local breakout="$3"
    local packageLocation=""
    [[ "$silent" -eq 0 ]] && dots "Installing package $package"
    $installer install "$package" -y > /dev/null 2>&1
    status="$?"
    if [[ $silent -eq 0 ]] ; then
        if [[ $status -eq 0 ]] ; then
            echo "Installed"
        else
            echo "Failed"
            [[ -n $breakout ]] && exit 5
        fi
    fi
}
setTimezone() {
    local serverTimeZone="$1"
    dots "Setting Timezone"
    timedatectl set-timezone $serverTimeZone
    [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
}
checkForRoot() {
    dots "Checking if I am root"
    currentUser=$(whoami)
    if [[ "$currentUser" == "root" ]]; then
        echo "I am $currentUser"
    else
        echo "I am $currentUser"
        exit
    fi

}
startAndEnableService() {
    local useSystemctl=$(command -v systemctl)
    local useService=$(command -v service)
    local theService="$1"
    dots "Restarting and enabling $theService"
    if [[ "$theService" == "mysql" || "$theService" == "mariadb" ]]; then
        local doMysqlAndMariadb="1"
    fi
    if [[ -e "$useSystemctl" ]]; then
        if [[ ! "$doMysqlAndMariadb" -eq 1 ]]; then
            systemctl enable $theService > /dev/null 2>&1
            systemctl restart $theService > /dev/null 2>&1
            [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
        else
            systemctl enable mysql > /dev/null 2>&1
            systemctl restart mysql > /dev/null 2>&1
            local mysqlTry=$?
            systemctl enable mariadb > /dev/null 2>&1
            systemctl restart mariadb > /dev/null 2>&1
            local mariadbTry=$?
            [[ "$mysqlTry" -eq 0 || "$mariadbTry" -eq 0 ]] && echo "Ok" || echo "Failed"
        fi
    elif [[ -e "$useService" ]]; then
        if [[ ! "$doMysqlAndMariadb" -eq 1 ]]; then
            service $theService enable  > /dev/null 2>&1
            service $theService restart > /dev/null 2>&1
            [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
        else
            service mysql enable > /dev/null 2>&1
            service mysql restart > /dev/null 2>&1
            local mysqlTry=$?
            service mariadb enable > /dev/null 2>&1
            service mariadb restart > /dev/null 2>&1
            local mariadbTry=$?
            [[ "$mysqlTry" -eq 0 || "$mariadbTry" -eq 0 ]] && echo "Ok" || echo "Failed"
        fi
    else
        echo "Unable to determine service manager"
    fi
}
setupFirewalld() {
    dots "Configure firewalld"
    #To remove services allowed through firewall, use the below line:
    #for service in http samba ntp; do firewall-cmd --permanent --zone=public --remove-service=$service; done > /dev/null 2>&1
    for service in http samba ntp; do firewall-cmd --permanent --zone=public --add-service=$service; done > /dev/null 2>&1
    local useSystemctl=$(command -v systemctl)
    local useService=$(command -v service)
    if [[ -e "$useSystemctl" ]]; then
        systemctl restart firewalld > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    elif [[ -e "$useService" ]]; then
        service firewalld restart > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Failed"
    fi
}
createUserJane() {
    dots "Creating user jane and setting password"
    doesJaneExist=$(getent passwd jane)
    if [[ -z "$doesJaneExist" ]]; then
        useradd jane > /dev/null 2>&1
        password=janepassword
        echo -e "$password\n$password\n" | sudo passwd jane > /dev/null 2>&1
        echo -e "$password\n$password\n" | smbpasswd -a jane > /dev/null 2>&1
        echo "Done"
    else
        echo "Exists"
    fi
}
setupDB() {
    dots "Checking for jane database"
    janeDBExists=$(mysql -s -N -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'jane'")
    if [[ "$janeDBExists" != "jane" ]]; then
        echo "Does not exist"
        dots "Creating jane database"
        mysql < dbcreatecode.sql > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
        dots "Storing existing users and groups"
        php $cwd/../service/initialStoreLocalUsersAndGroups.php  > /dev/null 2>&1
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Exists"
    fi
}
createDirectories() {
    dots "Setting up directories"
    if [[ ! -d "/jane" ]]; then
        mkdir /jane
    fi
    if [[ ! -d "/jane/imports" ]]; then
        mkdir /jane/imports
    fi
    if [[ ! -d "/jane/ssl" ]]; then
        mkdir /jane/ssl
    fi
    if [[ ! -d "/jane/log" ]]; then
        mkdir /jane/log
        touch /jane/log/JaneEngine.log
    fi
    if [[ -d "/jane/service" ]]; then
        if [[ -e "/jane/service/localVars.php" ]]; then
            if [[ -e "/home/localVars.php" ]]; then 
                rm -f /home/localVars.php
            fi
            cp /jane/service/localVars.php /home
        fi
        rm -rf /jane/service
    fi
    mkdir /jane/service
    cp -R $cwd/../service /jane
    if [[ ! -d "/jane/apacheTemp" ]]; then
        mkdir /jane/apacheTemp
    fi
    rm -rf /jane/apacheTemp/*
    if [[ -e "/home/localVars.php" ]]; then
        if [[ -e "/jane/service/localVars.php" ]]; then 
            rm -f /jane/service/localVars.php
        fi
        cp /home/localVars.php /jane/service
        rm -f /home/localVars.php
    fi
    if [[ -e "/jane/service/initialStoreLocalUsersAndGroups.php" ]]; then 
        rm -f /jane/service/initialStoreLocalUsersAndGroups.php
    fi
    if [[ -d "/var/www/html/jane" ]]; then
        if [[ -e "/var/www/html/jane/vars.php" ]]; then
            if [[ -e "/home/vars.php" ]]; then
                rm -f /home/vars.php
            fi
            cp /var/www/html/jane/vars.php /home
        fi
        if [[ -e "/var/www/html/jane/Jane.crt" ]]; then
            if [[ -e "/home/Jane.crt" ]]; then
                rm -f /home/Jane.crt
            fi
            cp /var/www/html/jane/Jane.crt /home
        fi
        rm -rf /var/www/html/jane
    fi
    cp -R $cwd/../jane /var/www/html
    touch /jane/apacheTemp/serverStatus.txt
    if [[ -e "/var/www/html/index.php" ]]; then
        rm -f "/var/www/html/index.php"
    fi
    mv /var/www/html/jane/redirect.php /var/www/html/index.php
    if [[ -e "/home/vars.php" ]]; then
        if [[ -e "/var/www/html/jane/vars.php" ]]; then
            rm -f /var/www/html/jane/vars.php
        fi
        cp /home/vars.php /var/www/html/jane
        rm -f /home/vars.php
    fi
    if [[ -e "/home/Jane.crt" ]]; then
        if [[ -e "/var/www/html/jane/Jane.crt" ]]; then
            rm -f /var/www/html/jane/Jane.crt
        fi
        cp /home/Jane.crt /var/www/html/jane
        rm -f /home/Jane.crt
    fi
    echo "Ok"
}
copyMysqlConfigFile() {
dots "Copying jane.cnf to mysql configuration directory"
if [[ "$ID" == "centos" ]]; then
    if [[ -d "/etc/my.cnf.d" ]]; then
        if [[ -e "/etc/my.cnf.d/jane.cnf" ]]; then
            rm -f "/etc/my.cnf.d/jane.cnf"
        fi
        cp $cwd/jane.cnf /etc/my.cnf.d/jane.cnf
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Directory /etc/my.cnf.d does not exist!"
    fi
elif [[ "$ID" == "fedora" ]]; then
    if [[ -d "/etc/my.cnf.d" ]]; then
        if [[ -e "/etc/my.cnf.d/jane.cnf" ]]; then
            rm -f "/etc/my.cnf.d/jane.cnf"
        fi
        cp $cwd/jane.cnf /etc/my.cnf.d/jane.cnf
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Directory /etc/my.cnf.d does not exist!"
    fi
elif [[ "$ID" == "rhel" ]]; then
    if [[ -d "/etc/my.cnf.d" ]]; then
        if [[ -e "/etc/my.cnf.d/jane.cnf" ]]; then
            rm -f "/etc/my.cnf.d/jane.cnf"
        fi
        cp $cwd/jane.cnf /etc/my.cnf.d/jane.cnf
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Directory /etc/my.cnf.d does not exist!"
    fi
else
    echo "MySql config directory unknown on this OS."
fi
}
checkCert() {
    dots "Checking for certificates"
    if [[ ! -e "/jane/ssl/Jane.key" || ! -e "/var/www/html/jane/Jane.crt" ]]; then
        echo "One or both certs missing"
        dots "Creating certs"
        [[ -e "/jane/ssl/Jane.key" ]] && rm -f /jane/ssl/Jane.key
        [[ -e "/var/www/html/jane/Jane.crt" ]] && rm -f /var/www/html/jane/Jane.crt
        openssl req -nodes -x509 -sha256 -newkey rsa:4096 -keyout "/jane/ssl/Jane.key" -out "/var/www/html/jane/Jane.crt" -days 99999 -passin pass:'' -subj "/C=''/ST=''/L=''/O=''/OU=''/CN=''/emailAddress=''" > /dev/null 2>&1 
        echo "Created new key pair." >> /jane/log/JaneEngine.log
        [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
    else
        echo "Exists"
    fi
}
setPermissions() {
    dots "Setting permissions"
    chown -R root:jane /jane/imports > /dev/null 2>&1
    chown -R root:apache /jane/ssl > /dev/null 2>&1
    chown -R root:apache /jane/service > /dev/null 2>&1
    chown -R root:apache /jane/log > /dev/null 2>&1
    chown -R root:apache /jane/apacheTemp > /dev/null 2>&1
    chmod -R 777 /jane > /dev/null 2>&1
    chmod -R 770 /jane/* > /dev/null 2>&1
    chown -R apache:apache /var/www/html/jane > /dev/null 2>&1
    chmod -R 555 /var/www/html/jane > /dev/null 2>&1
    chown apache:apache /var/www/html/index.php > /dev/null 2>&1
    chmod 555 /var/www/html/index.php > /dev/null 2>&1
    chmod 770 /jane/apacheTemp/serverStatus.txt
    chmod -R 770 /jane/apacheTemp > /dev/null 2>&1
    echo "Ok"
}
setSELinuxToPermissive() {
    dots "Setting SELinux to permissive"
    setenforce 0 > /dev/null 2>&1
    sed -i.bak 's/^.*\SELINUX=enforcing\b.*$/SELINUX=permissive/' /etc/selinux/config > /dev/null 2>&1
    [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
}
startJaneOnBoot() {
    dots "Enable JaneEngine on boot"
    if [[ -d "/etc/rc.d" ]]; then
        touch /etc/rc.d/rc.local
    else
        echo "No /etc/rc.d directory"
        exit
    fi
    janeEnabled=$(cat /etc/rc.d/rc.local | grep "/usr/bin/nohup /usr/bin/php /jane/service/JaneEngine.php &")    
    if [[ -z $janeEnabled ]]; then
        hasShaBang=$(cat /etc/rc.d/rc.local | grep "#!/bin/bash")
        if [[ -z $hasShaBang ]]; then
            echo "#!/bin/bash" >> /etc/rc.d/rc.local
        fi
        echo "sleep 30" >> /etc/rc.d/rc.local
        echo "/usr/bin/nohup /usr/bin/php /jane/service/JaneEngine.php &" >> /etc/rc.d/rc.local
        echo "exit 0" >> /etc/rc.d/rc.local
        chmod +x /etc/rc.d/rc.local
        echo "Enabled"
    else
        echo "Exists"
    fi
}
stopJaneEngine() {
    dots "Stopping JaneEngine"
    pkill -f JaneEngine.php > /dev/null 2>&1
    [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
}
startJaneEngine() {
    dots "Starting JaneEngine"
    pkill -f JaneEngine.php > /dev/null 2>&1
    nohup php /jane/service/JaneEngine.php >> /jane/log/JaneEngine.log &
    [[ $? -eq 0 ]] && echo "Ok" || echo "Failed"
}
completed() {
echo
echo "   Access the Jane web interface here:"
echo "   x.x.x.x/jane/login.php"
echo
echo "   Default credentials:"
echo "   administrator : changeme"
echo "   tech : changeme"
echo
echo
echo "   Setup complete"
echo
}


