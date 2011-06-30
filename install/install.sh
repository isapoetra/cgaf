#!/bin/bash -x
if [[ $EUID -ne 0 ]]; then
  sudo su
  if [[ $EUID -ne 0 ]]; then
  	exit
  fi
  echo "You must be a root user, please type " 2>&1
  exit 1
fi
source $(dirname $0)/simple_curses.sh

checkinstall()
{

	pac="${1}"
	installed=`aptitude search "~i ^$pac$"`
	if [ "$installed" = "" ];then
		echo "$pac not installed"
		aptitude -y install $pac
	fi
}
enmod() {
	a2enmod ${1}
}
main(){
	if [ INSTALL == "true" ]; then
		checkinstall "apache2"
		checkinstall "apache2-mpm-prefork"
		checkinstall "libapache2-mod-php5"
		checkinstall "mysql-server"
		checkinstall "php5-common"
		checkinstall "php5-mysql"
		checkinstall "php5-sqlite"
		enmod "alias"
		enmod "rewrite"
		enmod "expires"
		/etc/init.d/apache2 restart
	fi
	php -f cgaf.php
	exit
}
main_loop 1
