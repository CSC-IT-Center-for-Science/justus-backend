# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.require_version ">= 1.7.0"

Vagrant.configure("2") do |config|

  config.vm.box = "centos/7"
  config.vm.network :private_network, ip: "10.10.10.10"
  config.vm.network :forwarded_port, guest: 80, host: 8080
  config.vm.network :forwarded_port, guest: 5432, host: 5432, auto_correct: true
  config.vm.hostname = "justus"

  # Disable the new default behavior introduced in Vagrant 1.7, to
  # ensure that all Vagrant machines will use the same SSH key pair.
  # See https://github.com/mitchellh/vagrant/issues/5005
  config.ssh.insert_key = false

  config.vm.provision "ansible_local" do |ansible|
    ansible.inventory_path = "inventories/vagrant"
    ansible.limit = "all"
    ansible.playbook = "deploy.yml"
    ansible.provisioning_path = "/vagrant/ansible"
    ansible.verbose = "v"
  end
  config.vm.provider "virtualbox" do |vbox|
    vbox.memory = 2048
    vbox.gui = false
  end
end