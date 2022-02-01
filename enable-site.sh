#!/bin/bash
#This script will check the "sites-required" file to see if we need to set any up
#If we do, it will action the setup, before removing the created sites from the file

free_port=-1
start_port=60000
sites=( "WP" "band" "admin" )

#Gets the next available port and sets free_port
get_port() {
	curr_port=$start_port
	response=""
	while : ;
	do
		let curr_port+=1
		echo "Checking for site on port $curr_port"
		response=$(sudo lsof -i:$curr_port)

		if [ -z "$response" ]; then
			echo "No site found!"
			free_port=$curr_port
			start_port=$curr_port
			break
		fi
	done
}

#Creates the directory for a site and assigns the appropriate permissions
#Params: $1 - path to folder to create, $2 - type of site (e.g. WP = WordPress, band = Band Admin, admin = Organisation Admin)
create_site_directory(){
	dir="$1"
	sudo cp -r "/var/www/master/$2" "$dir"
	sudo chown www-data:www-data $dir
	echo "created $dir"
}

#Creates a virtualhost and enables the site
#Params: $1 - directory, $2 - ID
create_apache_site(){
	#copy template config file
	conf_file="/etc/apache2/sites-available/$2.conf"
	sudo cp /etc/apache2/sites-available/skeleton.conf "$conf_file"
	get_port

	#replace template fields
	sudo sed -i 's,##port##,'"$free_port"',' "$conf_file"
	sudo sed -i 's,##directory##,'"$1"',' "$conf_file"
	sudo sed -i 's,##id##,'"$2"',' "$conf_file"
	#TODO: db access

	#tell Apache to listen on free port
	echo "LISTEN $free_port" | sudo tee -a /etc/apache2/ports.conf

	#enable site
	sudo a2ensite "$2"
}

#Makes the magic happen - creates 3 sites for the given organisation
#Params: $1 = full name, $2 = ID
setup_site(){
	for site in "${sites[@]}"
	do
		echo "Creating $site site..."
		id="$2_$site"
		dir="/var/www/$id"
		create_site_directory "$dir" "$site"
		create_apache_site "$dir" "$id"
		echo "Created $site site at $wp_dir on port $free_port"
		echo ""
	done

#	echo "Creating band site..."
#	id="$2_band"
#	band_dir="/var/www/$id"
#	create_site_directory "$band_dir"
#	create_apache_site "$band_dir" "$id"
#	echo "Created band site at $band_dir on port $free_port"
#
#	echo "Creating admin site..."
#	id="$2_admin"
#	admin_dir="/var/www/$id"
#	create_site_directory "$admin_dir"
#	create_apache_site "$admin_dir" "$id"
#	echo "Created admin site at $admin_dir on port $free_port"
#

	echo "Restarting Apache (adding sites)"
	sudo systemctl restart apache2
}

#Writes completed sites to another file, in case this job gets interrupted
#Params: $1 - name, $2 - id
log_completed_site(){
	echo -e "$1\n$2" | sudo tee -a "/var/www/.actioned-sites"
}

#Removes both this file and the temp completed sites
remove_sites(){
	sudo rm "/var/www/sites-required"
	sudo rm "/var/www/.actioned-sites"
}

############
# MAIN START
############
echo "Checking for sites..."

#read the file line by line
count=0

name=""
id=""

while read -r line
do
	let count+=1
	if [ $(($count % 2)) -eq 0 ]; then
		id=$line
		echo "Found new organisation"
		echo "$name ($id)"
		setup_site "$name" "$id"
		log_completed_site "$name" "$id"
	else
		name=$line
	fi
done < "/var/www/sites-required"

remove_sites
