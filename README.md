## Main branch of distribution version of imgame application.
### Status

These notes reflect current deployment to imitationgame.co.uk and the clean codebase/SQL committed here ready for deployment/installation for use by anyone who wishes.
Documentation on how to use the software is available at http://imitationgame.co.uk/doc/info.html


### Thanks and acknowledgements
Thanks to all those people who have written, posted or blogged guides that I have used whilst preparing my systems. Big kudos.

### Guidance
Anyone wishing to install this software on a LAMP stack is welcome to contact martin.hall326 AT gmail.com for advice.
#### Deployment instructions
##### Summary
- Configure a LAMP stack server
- Deploy codebase to website
- Install clean SQL tables
- Domain specific information
- Operations

##### LAMP-stack server
This clean system has been built and configured on AlmaLinux9. The system must provide console access to be able to run CLI PHP scripts for STEP1 of any experiment.
The following guides are helpful in preparing a base server:- 

https://wiki.almalinux.org/series/LAMP-server.html#optional-step-adding-a-website

https://wiki.crowncloud.net/?How_to_Install_MySQL_on_AlmaLinux_9

https://wiki.crowncloud.net/?How_to_install_php_8_4_on_AlmaLinux_9

The production version is deployed to a Hybrid VPS host. Dev versions can be installed on local VMs (VMWare, Hyper-X etc). 

Ensure the MYSQL installation is secured and ensure at least the MySQL root user/password is known.

##### Codebase
If imgame will be the only website running on the server then copy the full codebase structure into the default web document directory. If not, configure a virtual host for an additional website. Ensure Apache has group ownership of the whole structure. (Apache on AlamLinux9, www-data on some Ubuntu systems).

If making changes to a dev version of the site, I find it useful to create an SFTP user and make this user a member of the apache or www-data group and use WinSCP to push code to the server.

##### SQL
Extract sql/igrt_clean.zip and use MySQL Workbench or equivalent to build a data schema and import the tables and stored-procedures into the schema. Create a database user with full DBA access to the schema, tables etc. Use these details to create the db connection string in domain specifics below.
The clean tables contain 1 admin user in igUsers and 1 fully defined experiment which can be cloned onto other new experiments.
The intial admin to login to the portal can be created or derived by:-
- modifying the existing user in igUsers (martin.hall326@gmail.com) and change to the required email address. The passwords in this table are encrypted hashes (all login operations just compare the hash of the submitted password with the hashed value stored in the table). So the password hash will also need to be updated to the required value. The hash alogrithm is sha256. Check /helpers/login/class.PasswordManager.php for details (or to implement other hashing if required).
- adding a new user to this table with the required details. Set permissions = 1024 (full admin) and activated = 1.

##### Domain specifics
The database connection is configured in /domainSpecific/mySQLObject.php
There is also a file /domainSpecific/cliEnvironment.php which is used to define the deployed domain name used in CLI PHP scripts such as the Step1 listener/controller.
If you are using SMTP to handle user registration, password management etc (recommended), then you will need to configure /classes/mailer.php

##### Operations
###### Documentation
There is in-built online documentation accessed from yourdomain/doc/info.html
###### SMTP
If the installer of the system will also be supporting and using the system and is reasonably competent with SQL then managing many of the aspects system can be directly achieved by manipulating tables, but if multiple users/researchers will be using the system then it is recommended that the system be configured as an SMTP server (or linked to an existing SMTP server) so that the full suite of user management functions can be used.
If this is the case, then the system will need to be hardened so that emails do not get rejected by major emails systems such as gmail, MS, etc. The following guides may be helpful:-

https://serverfault.com/questions/679083/how-to-set-the-username-and-password-for-smtpd-in-postfix

https://mellowhost.com/blog/installing-and-configuring-postfix-with-authentication-on-almalinux-9.html

https://www.siberoloji.com/install-postfix-configure-smtp-server-almalinux/
https://www.google.com/search?q=postfix+SPF+authentication&rlz=1C1UEAD_en-GBGB1166GB1167&oq=postfix+SPF+authentication&gs_lcrp=EgZjaHJvbWUyBggAEEUYOTIICAEQABgWGB4yBwgCEAAY7wUyCggDEAAYgAQYogQyBwgEEAAY7wUyBwgFEAAY7wUyBwgGEAAY7wXSAQkxMTgwMWowajeoAgCwAgA&sourceid=chrome&ie=UTF-8

https://easydmarc.com/blog/how-to-configure-dkim-opendkim-with-postfix/



