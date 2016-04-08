# This is not a full blown installer yet, more of a guide at this point.
# The below instructions are good for CentOS 7 and Fedora 21+. Fedora 22 and up will give messages about yum being depcreciated but for now that's just fine.

#Right now, this is just a dumping ground for what is needed to make Jane work.



#Update server
yum update -y

#install packages

# FEDORA 23
dnf -y install mariadb mariadb-server php httpd php-mysqlnd php-gd php-mhash php-mcrypt samba samba-client



#CentOS 7

#  yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
#  yum install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
#  yum install yum-utils
#  subscription-manager repos --enable=rhel-7-server-optional-rpms
#  yum-config-manager --enable remi-php70
#  yum -y install mariadb mariadb-server php httpd php-mysqlnd php-gd php-mhash php-mcrypt samba samba-client




#apache setup.
systemctl start httpd
systemctl enable httpd

#Firewalld setup.
for service in http samba; do firewall-cmd --permanent --zone=public --add-service=$service; done
systemctl enable firewalld.service
systemctl restart firewalld.service

#create user Jane
useradd jane
local password=uyspBj[D5)s3b2vv
echo -e "$password\n$password\n" | sudo passwd jane
smbpasswd -a jane
uyspBj[D5)s3b2vv

mysql < dbcreatecode.sql
php initialStoreLocalUsersAndGroups.php
mkdir /jane
mkdir /jane/imports
#set selinux to permissive.
setenforce 0
systemctl enable smb

#Additionally, make sure the jane php files is put into /var/www/html/jane
#set permissions on that with:  

#   chown -R apache:apache /var/www/html/jane
#   chmod -R 555 /var/www/html/jane

