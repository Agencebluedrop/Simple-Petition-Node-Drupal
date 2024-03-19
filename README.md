# Simple-Petition-Node-Drupal
Simple Petition Node (SPN) is a petition extension on Drupal which manages campaigns for the users and the organizations and facilitates petitions for the public.  

SPN helps the user promote its petition by letting him create the petition, manage the emails and notifications and view results.

An easy to use module to manage petitions on Drupal.

# Installation & Functionalities
SPN can be enabled like any other extension in Drupal back office: /admin/modules
Upon installation, the module creates a content type “Petition” with predefined fields (Title, Body, Validation and Confirmation Emails fields and their corresponding subjects fields) and adds two blocks, Petition signing form and Petition results block, that can be managed in Drupal’s back office.
A configuration page is available at /admin/config/spn/notifications to manage the default emails and notifications messages for all the petitions. Tokens are available to use variables related to the petition.
Additionally, SPN adds two tables to the Drupal’s schema, “Petition Signatures” and “Petition Users”. The first will contain data about each signature made on any petition and the latter will contain the info of the anonymous users that are signing the petitions... 

# Utilization
The “creating a petition” process is the same as adding any Drupal content. By simply going to /node/add and choosing the petition content type then filling the fields and saving the content.
Note that, if the emails fields for a content are left empty, the module will use the default emails defined in the configuration page of the module (/admin/config/spn/notifications).

After creating the content, the user has to add the two blocks to the pages he desires. By directing to  /admin/structure/block and adding the bocks to the preferred region in the page. A template suggestions will be available to change the blocks layout and take control of the structure and design of each block.
The blocks controllers function following the route of the page they are on, so adding the blocks once in Drupal’s block configuration page will be enough, the blocks will show on all the petitions node pages

# Signing a petition
To sign a petition, users can be logged in or anonymous, they are invited to fill in the required infos and add a comment if desired. Moreover, they can choose to sign anonymously, if the corresponding field is checked, their infos and comment won’t appear in the results block but their vote will be counted. After signing, the subscriber will receive an email notification with a link to validate its signature, if not validated, the vote won’t be counted. And after validation, he will receive a confirmation email.

# Exporting Signatures to CSV file
Administrators can export the signatures by going to the configuration page at  /admin/spn/content/petitions
Make sure the "private" directory is well configured on your server. You can check it from the back office at /admin/config/media/file-system bye making sure the "Private file system path" is well assigned.

# Extensions
Some extensions are being developed for future releases, administer petitions pages, administer petition fields, export pdf, routes and permissions...

# Credits
Simple Petition Node is developed and maintained by <a href="https://bluedrop.fr">bluedrop.fr</a>.

<a href="https://www.cgt.fr/" title="CGT">Authorized by Confédération Générale du Travail - CGT.</a>
