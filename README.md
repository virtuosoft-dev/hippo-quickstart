# hcpp-quickstart
A plugin for Hestia Control Panel (via hestiacp-pluginable) that adds the Quickstart tab for an easy-to-use guide and quick website setup.

&nbsp;
 > :warning: !!! Note: this repo is in progress; when completed, a release will appear in the release tab.
 
&nbsp;
## Installation
HCPP-Quickstart requires an Ubuntu or Debian based installation of [Hestia Control Panel](https://hestiacp.com) in addition to an installation of [HestiaCP-Pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable) to function; please ensure that you have first installed pluginable on your Hestia Control Panel before proceeding. Switch to a root user and simply clone this project to the /usr/local/hestia/plugins folder. It should appear as a subfolder with the name `quickstart`, i.e. `/usr/local/hestia/plugins/quickstart`.

First, switch to root user:
```
sudo -s
```

Then simply clone the repo to your plugins folder, with the name `quickstart`:

```
cd /usr/local/hestia/plugins
git clone https://github.com/virtuosoft-dev/hcpp-quickstart quickstart
```

Note: It is important that the destination plugin folder name is `quickstart`.


Be sure to logout and login again to your Hestia Control Panel as the admin user or, as admin, visit Server (gear icon) -> Configure -> Plugins -> Save; the plugin will immediately start installing quickstart depedencies in the background. 

A notification will appear under the admin user account indicating *”Quickstart plugin has finished installing”* when complete. This may take awhile before the options appear in Hestia. You can force manual installation via:

```
cd /usr/local/hestia/plugins/quickstart
./install
touch "/usr/local/hestia/data/hcpp/installed/quickstart"
```

&nbsp;
## About Quickstart
This plugin will furnish a new tab item 'Quickstart' which acts as an easy-to-use, guided 'wizard' to easily create a domain name and associated website backend. 
<br>

## Support the creator
You can help this author’s open source development endeavors by donating any amount to Stephen J. Carnam @ Virtuosoft. Your donation, no matter how large or small helps pay for essential time and resources to create MIT and GPL licensed projects that you and the world can benefit from. Click the link below to donate today :)
<div>
         

[<kbd> <br> Donate to this Project <br> </kbd>][KBD]


</div>


<!—————————>

[KBD]: https://virtuosoft.com/donate

https://virtuosoft.com/donate