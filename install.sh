#!/usr/bin/env bash

# Define echo function
# Blue color
function ox_lib_echo()
{
  echo $(tput setaf 4)$@$(tput sgr0)
}
# White color
function ox_lib_echo_info()
{
  echo $(tput setaf 7)$@$(tput sgr0)
}
# Red color
function ox_lib_echo_fail()
{
  echo $(tput setaf 1)$@$(tput sgr0)
}

# Checking permissions
function ox_lib_check_sudo()
{
  if [[ $EUID -ne 0 ]]; then
    ox_lib_echo_fail "Sudo privileges required..."
    exit 100
  fi
}

# Install Ox
function ox_install()
{
  ox_lib_echo "Install pre depedencies(Git, Curl, Gzip & Tar), please wait..."
  apt-get -y install git curl gzip tar || ee_lib_error "Unable to install pre depedencies, exit status " 1
  ox_lib_echo "Install PHP-CLI 7.1, please wait..."
  add-apt-repository -y 'ppa:ondrej/php' -y
  apt-get update &>> /dev/null
  apt-get -y install php7.1-cli || ox_lib_echo_fail "Unable to install PHP-CLI 7.1, exit status " 1
  ox_lib_echo "Create Ox database folder, please wait..."
  if [ ! -d /var/lib/ox/ ]; then
    mkdir -p /var/lib/ox/
    chown -R root:root /var/lib/ox/
    chmod -R 600 /var/lib/ox/
  else
    ox_lib_echo "Ox database folder already exists"
  fi
}

# Starting script point
ox_lib_check_sudo

ox_lib_echo_info "Starting Ox install process..."

# Execute: apt-get update
ox_lib_echo "Executing apt-get update, please wait..."
apt-get update &>> /dev/null

# Checking lsb_release package
if [ ! -x /usr/bin/lsb_release ]; then
  ox_lib_echo "Installing lsb-release, please wait..."
  apt-get -y install lsb-release &>> /dev/null
fi

# Checking linux distro
lsb_release -d | grep -e "Ubuntu 16.04" &>> /dev/null
if [ "$?" -ne "0" ]; then
    ox_lib_echo_fail "Ox only supports Ubuntu 16.04"
    exit 100
fi

ox_install
