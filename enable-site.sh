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
#Params: $1 - directory, #2 - site ID
create_apache_site(){
	#copy template config file
	conf_file="/etc/apache2/sites-available/$2.conf"
	sudo cp /etc/apache2/sites-available/skeleton.conf "$conf_file"
	get_port

	#replace template fields
	sudo sed -i 's,##port##,'"$free_port"',' "$conf_file"
	sudo sed -i 's,##directory##,'"$1"',' "$conf_file"
	sudo sed -i 's,##id##,'"$2"',' "$conf_file"
	sudo sed -i 's,##dbuser##,'"$db_user"',' "$conf_file"
	sudo sed -i 's,##dbpass##,'"$db_pass"',' "$conf_file"

	#tell Apache to listen on free port
	echo "LISTEN $free_port" | sudo tee -a /etc/apache2/ports.conf

	#enable site
	sudo a2ensite "$2"
}

#Makes the magic happen - creates 3 sites for the given organisation
#Params: $1 - site name, $2 - site ID, $3 - db user, $4 - db pass
setup_site(){
	for site in "${sites[@]}"
	do
		echo "Creating $site site..."
		id="$2_$site"
		dir="/var/www/$id"
		create_site_directory "$dir" "$site"
		create_apache_site "$dir" "$id"
		echo "Created $site site at $dir on port $free_port"
		echo ""
	done

	echo "Restarting Apache (adding sites)"
	sudo systemctl restart apache2
}

############
# MAIN START
############
echo "Checking for sites..."

files="/var/www/sites-required/*"
for f in $files
do
	if [ -f "$f" ]; then
		echo "$f"
		source "$f"
		echo "Processing site for $name..."
		setup_site "$name" "$id" "$db_user" "$db_pass"
		echo ""

		sudo rm "$f"
	fi
done
