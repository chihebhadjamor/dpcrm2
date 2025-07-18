#!/bin/bash

echo "Fixing GitHub remote URL..."

# Display current remote configuration
echo "Current remote configuration:"
git remote -v

# Update the remote URL with the correct username
echo -e "\nUpdating remote URL..."
git remote set-url origin https://github.com/chihebhadjamor/dpcrm2.git

# Verify the change
echo -e "\nNew remote configuration:"
git remote -v

echo -e "\nRemote URL has been updated. Now you can push using:"
echo "git push -u origin master"
echo -e "\nRemember to use your personal access token when prompted for password."
echo "If you haven't created a personal access token yet, visit:"
echo "https://github.com/settings/tokens"
