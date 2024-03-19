Simple Petition Node module for Drupal
======================================

The simple petition node module offers to create a petition as a node with automatically generated form block and results block.


DEPENDENCIES
------------

* depends on the CAPTCHA and reCaptcha modules.
  https://drupal.org/project/captcha
  https://drupal.org/project/recaptcha



CONFIGURATION
-------------

1. Enable the module at admin/modules

2. A content type "petition" will be created And a notifications configuration page at admin/config/spn/notifications

3. Two blocks will be available after enabling the module: Form and Results.

4. Go to Block Layout configuration page (/admin/structure/block) and add the blocks in the region you prefer in your theme.
   You can set conditions on access...
   Templates suggestions will be available for both blocks to change the HTML/Twig Layout

