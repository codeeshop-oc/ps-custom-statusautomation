#!/bin/bash
source $1/prestashop.env
source $1/scripts/functions.sh

read -p "Enter Git Path[Default - $ENV_GIT_PATH]: " CURR_GIT_PATH
CURR_GIT_PATH=${CURR_GIT_PATH:-$ENV_GIT_PATH}
CURR_PATH=$(pwd)

# TEST=ps-voucher-link
read -p "Enter Repo[Default - ${CURR_PATH##*/}]: " CURR_REPO_NAME
# CURR_REPO_NAME=${CURR_REPO_NAME:-$TEST}
CURR_REPO_NAME=${CURR_REPO_NAME:-${CURR_PATH##*/}}

isFolderExist "$CURR_GIT_PATH/$CURR_REPO_NAME"
if [ $? -eq 0 ]; then
	exit 1
fi

FOLDER_NAME=$(echo ${CURR_REPO_NAME} | sed -e "s/ps-//g")
FOLDER_NAME=$(echo $FOLDER_NAME | sed -e "s/-//g")
# echo "Folder Name - $FOLDER_NAME"

CURR_GIT_REPO_FULL_PATH="$CURR_GIT_PATH/$CURR_REPO_NAME/$FOLDER_NAME"
isFolderExist "$CURR_GIT_REPO_FULL_PATH"
if [ $? -eq 0 ]; then
	exit 1
fi

source $CURR_GIT_PATH/$CURR_REPO_NAME/.env

if [ ! $ENV_PRESTASHOP_ID_PRODUCT ]; then
	echo "ENV_PRESTASHOP_ID_PRODUCT not found"
	exit 1
fi

echo "Installation Path - $CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME"
read -p "Start Processing [options yes/no - default yes]?" START
START=${START:-'yes'}

if [ $START != 'yes' ]; then
	echo 'Action Cancelled'
	exit 1
fi

# cp -r $ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH ${CURR_GIT_REPO_FULL_PATH}
rsync -a $ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/* ${CURR_GIT_REPO_FULL_PATH}

ifTextNotPresentModifyConfig $CURR_GIT_REPO_FULL_PATH/views/templates/admin/configure.tpl "__MY_MODULE_NAME__" "$FOLDER_NAME"

FILE_TO_COPY_FROM=$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_SCRATCH_ONLY_PATH/1.txt
FILE_TO_COPY_TO=$CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME.php
SEARCH_TEXT="public function __construct()"

appendTextToNextLineInFile "\$this->id_seller_product =" "$FILE_TO_COPY_FROM" "$FILE_TO_COPY_TO" "$SEARCH_TEXT" 2

ifTextNotPresentModifyConfig $CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME.php "__PRESTASHOP_ID_PRODUCT__" "$ENV_PRESTASHOP_ID_PRODUCT"

FILE_TO_COPY_FROM=$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_SCRATCH_ONLY_PATH/2.txt
FILE_TO_COPY_TO=$CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME.php
SEARCH_TEXT="public function getContent()"

appendTextToNextLineInFile "\$output = '';" "$FILE_TO_COPY_FROM" "$FILE_TO_COPY_TO" "$SEARCH_TEXT" 1

FILE_TO_COPY_FROM=$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_SCRATCH_ONLY_PATH/3.txt
FILE_TO_COPY_TO=$CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME.php
SEARCH_TEXT="\$this->postProcess();"

appendTextToNextLineInFile "\$output .= \$this->displayConfirmation('Successfully saved')" "$FILE_TO_COPY_FROM" "$FILE_TO_COPY_TO" "$SEARCH_TEXT" 0

FILE_TO_COPY_FROM=$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_SCRATCH_ONLY_PATH/4.txt
FILE_TO_COPY_TO=$CURR_GIT_REPO_FULL_PATH/$FOLDER_NAME.php
SEARCH_TEXT="public function getContent()"

appendTextToNextLineInFile "id_seller_product' => \$this->id_seller_product" "$FILE_TO_COPY_FROM" "$FILE_TO_COPY_TO" "$SEARCH_TEXT" 2

subl $FILE_TO_COPY_TO 

cp "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/index.php" "$CURR_GIT_REPO_FULL_PATH/index.php"
copy_file_recursive "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/index.php" "$CURR_GIT_REPO_FULL_PATH"
cp "$ENV_GIT_CORE_TEMPLATE_SCRIPTS_FILES_PATH/.htaccess" "$CURR_GIT_REPO_FULL_PATH/.htaccess"