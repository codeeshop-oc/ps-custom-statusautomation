#!/bin/bash
source $1/prestashop.env
source $1/scripts/functions.sh

copy_file_recursive() {  
	local FILE_PATH=$1
	local CURR_DIR=$2	
	echo $CURR_DIR
	  for dir in "$CURR_DIR"/*; do
	    if [ -d "$dir" ]; then
		      # Copy the index.php file to the subdirectory
			echo "DIR => $dir"
			cp "$FILE_PATH" "$dir";

	      # Recursively copy the index.php file to all subdirectories of the current subdirectory
	      copy_file_recursive "$FILE_PATH" "$dir"

	    fi
	  done
}

# CURR_INSTALLATION_PATH=/home/anant/git/ps-voucher-link
read -p "Enter Git Path[Default - $ENV_GIT_PATH]: " CURR_GIT_PATH
CURR_GIT_PATH=${CURR_GIT_PATH:-$ENV_GIT_PATH}
CURR_PATH=$(pwd)
CURR_REPO_NAME=${CURR_PATH##*/}
read -p "Enter Repo[Default - ${CURR_REPO_NAME}]: " CURR_REPO_NAME

isFolderExist "$CURR_GIT_PATH/$CURR_REPO_NAME"
if [ $? -eq 0 ]; then
	exit 1
fi

FOLDER_NAME=$(echo ${CURR_REPO_NAME} | sed -e "s/ps-//g")
FOLDER_NAME=$(echo $FOLDER_NAME | sed -e "s/-//g")
echo "Folder Name - $FOLDER_NAME"

CURR_GIT_REPO_FULL_PATH="$CURR_GIT_PATH/$CURR_REPO_NAME/$FOLDER_NAME"
isFolderExist "$CURR_GIT_REPO_FULL_PATH"
if [ $? -eq 0 ]; then
	exit 1
fi

read -p "Start Processing [options yes/no - default yes]?" START
START=${START:-'yes'}
echo "$CURR_INSTALLATION_PATH/$FOLDER_NAME"

if [ $START != 'yes' ]; then
	echo 'Action Cancelled'
	exit 1
fi

cp "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/index.php" "$CURR_GIT_REPO_FULL_PATH/index.php"
copy_file_recursive "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/index.php" "$CURR_GIT_REPO_FULL_PATH"
cp "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/.htaccess" "$CURR_GIT_REPO_FULL_PATH/.htaccess"