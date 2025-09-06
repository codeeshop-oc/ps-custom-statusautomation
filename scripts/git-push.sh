#!/bin/bash
# ref: https://help.github.com/articles/adding-an-existing-project-to-github-using-the-command-line/
#
# Usage example: /bin/sh ./git_push.sh wing328 swagger-petstore-perl main "minor update"

source "$1/prestashop.env"

read -p "Enter Current Path [Default - $(pwd)]: " CURR_INSTALLATION_PATH
CURR_INSTALLATION_PATH=${CURR_INSTALLATION_PATH:-$(pwd)}

read -p "Repo Name - [default - ${CURR_INSTALLATION_PATH##*/}]" git_repo_id
git_repo_id=${git_repo_id:-${CURR_INSTALLATION_PATH##*/}}

read -p "Release Notes - [default - $ENV_PUSH_COMMENT]" release_note
release_note=${release_note:-$ENV_PUSH_COMMENT}

if [ "$git_repo_id" = "" ]; then
    echo "[INFO] No command line input provided. Set \$git_repo_id"
    exit 1
fi

if [ "$release_note" = "" ]; then
    release_note="Minor update"
    echo "[INFO] No command line input provided. Set \$release_note to $release_note"    
fi

# Adds the files in the local repository and stages them for commit.
git add .

# Commits the tracked changes and prepares them to be pushed to a remote repository. 
git commit -m "$release_note"

git pull origin $git_branch

# Pushes (Forces) the changes in the local repository up to the remote repository
echo "Git pushing to https://github.com/____/${git_repo_id}.git"
git push origin $git_branch