=== Guest posting / Frontend Posting wordpress plugin - WP Front User Submit / Front Editor ===
Contributors: aharonyan, freemius
Tags: frontend post,guest post,public post,submit post,user post
Requires at least: 4.0
Tested up to: 6.7.1
Requires PHP: 7.0
Stable tag: 4.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables users to submit post content from Front End. Use our plugin to implement guest posting

== Description ==
[🌐 Demo](http://demo.wpfronteditor.com/) | [📖 Documentation](https://wpfronteditor.com/docs) | [💬 Community](https://t.me/+loTEjPRS6lw3NTli) | [🚀 Upgrade to PRO](https://wpfronteditor.com/)  

WP Front User Submit is a versatile WordPress plugin designed to enable post submissions from the frontend with or without user login. Packed with configurable options, it offers a comprehensive solution for guest posting.

<strong>✨ Core Features</strong>

* Includes a fast & secure post-submission form
* Includes a simple login/register/password form
* Display forms anywhere via shortcode or template tag
* Flexibility for Admins
* Drag and Drop Form Builder
* Guest Post Support
* Admin and User Notification Configurations
* Redirection Options After Submission
* Configure Submitted Post Status
* Enable/Disable Form Components
* Multiple Text Editors (EditorJS, MD Editor, TinyMCE Editor, Simple Text Area)
* Use anywhere easily with shortcodes
* Simple Login and Registration Forms [fus_form_login] & [fus_form_register]
* Redirect user to any URL or current page after submission
* Use the default form styles or add your own custom CSS
* Form fields may be set as optional or required
* Includes shortcode to display a list of submitted posts [fe_fs_user_admin]
* Post Images
* Google reCAPTCHA Integration
* Responsive and Browser Compatible
* Developer Documentation Available
* WooCommerce integration: Enable payment collection for each post submission.

<strong> Detailed Features </strong>

<strong>Flexibility for Admins</strong>
Manage users from the frontend and configure backend access for specific users.

<strong>Files & attachments</strong>

Allow users to upload attachments, including post featured images, directly from the frontend.

<strong>Drag-n-Drop Form Builder</strong>

Build and customize forms with ease using the drag-and-drop form builder with real-time preview.

<strong>Shortcodes</strong>

Use unique shortcodes to embed forms anywhere on your site without breaking your theme's style.

<strong>Guest Post Submission</strong>

Enable guests to submit posts from the frontend without registering.

<strong>Frontend Content Management</strong>

Users can upload files, fill out forms, and update their posts directly from the frontend.

<strong>Next-Generation Block Styled Editor (EditorJS)</strong>

Enhance post content with block-styled editing capabilities.

<strong>Customizable Post Status and Messages</strong>

Set default post statuses, customize submission messages, and modify submit button text.

<strong>Display Custom Fields Data in Post</strong>

Custom fields data are viewable to visitors on frontend on single post pages. Admins can disable this feature also.

<strong>User Admin Panel</strong>
Manage posts with ease, including editing and deleting capabilities, using the [fe_fs_user_admin] shortcode.


<strong>Integrations</strong>

* Compatible with the User Role Editor plugin for advanced permission configurations.
* Compatible with ACF plugin.

<strong>✨ Premium Features</strong>

* Custom taxonomy support
* Custom post types
* Custom field support
* FilePond integration for file uploads
* Custom field with various field types
	 - Textfield
	 - Textarea
	 - Number
	 - Email
	 - URL
	 - Tel
* Enhanced EditorJS features (Gallery, Image uploading, Table, Carousel, etc.)
* Thumbnail using WP Media Uploader
* Multiple categories selection
* Files and images advanced uploader field using Filepond JavaScript library
* Google Map Field
* Date Field
* Hidden field
* Radio Group field
* Number field
* Button field
* Header field
* Checkbox Group field
* Paragraph field
* Action hook field
* hCaptcha field
* WooCommerce integration

*Boost your site value with user-generated content!*

<strong>Try It Out</strong>
* [Online Demo](http://demo.wpfronteditor.com/wp-login.php) of the FREE & PRO version.
* [Login Here](http://demo.wpfronteditor.com/wp-login.php)
* Username: Demo
* Password: Demo

== Please help us to improve the plugin ==
For revision and issues [here](https://wpfronteditor.com/docs)

== Pro Version ==
* Front Editor Pro* now available [here](https://wpfronteditor.com/).

== Community ==
https://wpfronteditor.com/docs/

== Documentation ==
* Check documentation [here](https://wpfronteditor.com/docs)


For more information please visit [our site](https://wpfronteditor.com/) .

== Installation ==
Front Editor via Gutenberg block or shortcode that enables your visitors to submit posts and upload images. Just add the following shortcode to any Post, Page, or Widget:	
* Gutenberg block:`Front editor`
* Shortcode: `[bfe-front-editor]`
To add user admin page please add shortcode below:
* Shortcode: [fe_fs_user_admin]

That's all there is to it! Your site now can accept user-generated content. Everything is super easy to customize via the Plugin Settings page.

== Translations ==

* English - default, always included
* Russian - ru_RU

== Frequently Asked Questions ==
**Can I create new posts from frontend**
Yes
**Can I Edit my posts from frontend**
Yes
**Can I delete my posts from frontend**
Yes
**Can I upload photo/image/video**
Yes
**I am having problem with uploading files**
Please check if you've specified the max upload size on setting
**Can I translate plugin**
Yes! plugins are localized/ translatable by default. For translating I recommend the awesome plugin [Loco Translate](https://wordpress.org/plugins/loco-translate/).
**Is working with Gutenberg Block Editor ?**
Works perfectly with or without Gutenberg Block Editor
**How to use Shortcode with settings ?**
Please check this article [https://wpfronteditor.com/how-to-use-shortcode/](https://wpfronteditor.com/how-to-use-shortcode/)

== Screenshots ==
1. Form Settings
2. Form Settings
3. Form Settings
4. Form Settings
5. Form Settings
6. Form Settings
7. Form Settings
8. Form Settings
9. Form Settings
10. Form Settings
11. Form Settings
12. Form Settings
13. Form Settings
14. Form Settings
15. Form Settings
16. Form Settings
17. Form Settings

== Changelog ==

= 4.9.1 =
* Bug fixes
* [new] action hook field
* [new] hCaptcha field added
* [new] shortcode added [fus_google_map meta_name="your_meta_field_name"] to show map content
* [new] shortcode added [fus_custom_field_content meta_name="user_email"]

= 4.9.0 =
* Bug fixes

= 4.8.9 = 
* Bug fixes

= 4.8.8 = 
* Fixed issue with admin bar behavior added new option "default"
* [EditorJS] Fixed ui bug
* [EditorJS] Updated

= 4.8.5 = 
* Fixed bug with replacing content
* Added Russian translation
* Fixed bug _load_textdomain_just_in_time was called incorrectly

= 4.8.3 = 
* Required logic bug fixed
* Empty post title and post content issue
* Added shortcode to show simple field value in post: [fus_display_field name="{field_name}"]

= 4.8.0 = 
* Fixed issue with File field when uploading video
* Fixed registration notification bug
* Fixed date field required functionality
* Added Meta Box to the post create by the plugin form
* Buttons both on top and bottom
* Fixed custom css issue
* Notification admin default email now will be admin email
* In Taxonomy and Select fields search input deactivate and can be activated in settings
* TinyMCE added 3 new settings media buttons, making teeny, activating drag and drop functionality

= 4.7.9 = 
* All fields require field issue fixed
* In all field now you can modify require issue message
* All select elements added settings to change search placeholder and No Result texts
* Multiple editor fields in one form with with selection where to save content (meta, post content, excerpt)
* EditorJS field added Placeholder settings
* New settings to enable or disable preview button
* New settings to change add new and preview buttons texts from form settings
* WooCommerce integration order checking issue fixed
* Fixed title generation with new taxonomy

= 4.7.0 = 
* WooCommerce integration ready
* Fixed image issue in editor js
* Fixed edit button in posts issue

= 4.6.7 = 
* Security bug fixed
* MD editor full screen issue fixed

= 4.6.5 =
* Migration settings added
* Date field added
* Google map field added
* MD editor editing post fixed added html to markdown parser
* Added settings to User Admin to show login form or change not logged in message
* Changed permissions from delete_pages -> edit_others_posts to work with permission plugins
* Remove unnecessary input from form 
* Now we are using Rest API instead of admin ajax with improving security

= 4.6.0 =
* Added settings page for user admin page
* New field in builder Number field
* New field in builder Button field
* New field in builder Header field
* New field in builder Checkbox Group field
* New field in builder Paragraph field

= 4.5.5 = 
* MD editor now working correctly
* Added login and registration settings page
* Security fixes
* Added hidden field
* Default terms settings added in taxonomy fields
* Added Radio Group field

= 4.5.0 = 
* fixed issue when saving post
* added google reCaptcha field

= 4.4.8 =
* issue fixed

= 4.4.7 =
* Security updates
* Fixed issue with publishing

= 4.4.5 =
* Fixed security issues

= 4.4.1 =
* Fixed post type bug

= 4.4.0 =
* [bug] Featured image was fixed
* [bug] Fixed issue with the links
* [New] Lock user from editing after

= 4.3.5 =
* Fixed issues with arabic and Russian language
* Added hidden text field type
* Added text input to change placeholder text in EditorJS Quote block
* Added text input to change placeholder text in EditorJS Header block

= 4.3.0 =
* Now you can hide edit button on posts
* Now you can add your custom text to edit button
* You can now change submit buttons border color and radius
* Title issue fixed
* Every form now have its own settings for selecting edit page
* User dashboard default is 6 posts

= 4.2.0 =
* Login settings in form
* [Post Title Field] Refactoring
* [Post Title Field] Hide field setting
* [Post Title Field] Generate title using other field and taxonomy selected by user
* Required icon in label for all fields
* Ordering files in File Field fixed

= 4.1.2 =
* Taxonomy bug hot-fix
* Small fixes on showing restriction messages

= 4.1.0 =
* New Select custom field
* New File upload field
* Fixed small issues
* Fixed icons issue in form builder in admin
* [Taxonomy] fields add new button issue fixed
* [Taxonomy] fields can be now shown hierarchically
* [Taxonomy] order field fixed
* [Taxonomy] added field in settings to exclude terms by id

= 4.0.4 =
* [EditorJS] fixed issue with toolbox
* Fixed issue with post image duplication
* Updating URL after post successfully added
* Updating submit button text to update button text after post successfully added

= 4.0.0 =
* ui settings fix
* new default design
* fixe issue with redirect link
* fixed tinyMCE editor not saving bug
* added button background and font color picker

= 3.9.6 =
* small ui issue fixed
* security fix
* align issue fixed
* [editor.js] quote fixed now the author in bottom
* added new content editor TinyMCE
* added 3 type of notification with settings on post publish, submit,trash

= 3.9.2 =
* inline tool activated again for paragraph
* fixed small issue in admin

= 3.9.0 =
* align settings added to header and text blocks in editor.js
* login and registration Shortcodes added with redirect parameter
* added logout button to user admin
* added control buttons place settings show in the top or in the bottom
* added settings to control success and error messages places in form
* [editor.js] warning block issue fixed [Pro]
* [editor.js] table block activated with new version [Pro]
* added new field in form builder textarea
* added loader on front form

== Upgrade Notice ==

= 4.9.1 =
New fields, shortcodes and bug fixes. Check it out.

= 4.9.0 =
Bug fixes

= 4.8.9 =
Bug fixes

= 4.8.8 =
Fixed EditorJS and navigation bar.

= 4.8.5 =
This update includes several hot fixes and new translations.

= 4.8.3 =
This update includes several hot fixes and new shortcode.

= 4.8.0 =
This update includes several fixes and new features.

= 4.7.9 = 
* All fields require field issue fixed
* In all field now you can modify require issue message
* All select elements added settings to change search placeholder and No Result texts
* Multiple editor fields in one form with with selection where to save content (meta, post content, excerpt)
* EditorJS field added Placeholder settings
* New settings to enable or disable preview button
* New settings to change add new and preview buttons texts from form settings
* WooCommerce integration order checking issue fixed
* Fixed title generation with new taxonomy

= 4.7.2 = 
* WooCommerce integration order checking issue fixed
* Fixed title generation with new taxonomy

= 4.7.0 = 
* WooCommerce integration ready
* Fixed image issue in editor js
* Fixed edit button in posts issue

= 4.6.7 = 
* Security bug fixed
* MD editor full screen issue fixed

= 4.6.5 =
* Migration settings added
* Date field added
* Google map field added
* MD editor editing post fixed added html to markdown parser
* Added settings to User Admin to show login form or change not logged in message
* Changed permissions from delete_pages -> edit_others_posts to work with permission plugins
* Remove unnecessary input from form 
* Now we are using Rest API instead of admin ajax with improving security

= 4.6.0 =
* Added settings page for user admin page
* New field in builder Number field
* New field in builder Button field
* New field in builder Header field
* New field in builder Checkbox Group field
* New field in builder Paragraph field

= 4.5.0 = 
* Added a lot of new functionality 

= 4.5.0 = 
* fixed issue when saving post
* added google reCaptcha field

= 4.4.8 =
* issue fixed

= 4.4.7 =
* Security updates
* Fixed issue with publishing

= 4.4.5 =
* Fixed security issues

= 4.4.1 =
Fixed post type bug

= 4.4.0 =
* [bug] Featured image was fixed
* [bug] Fixed issue with the links
* [New] Lock user from editing after

= 4.3.5 =
Bug fixes and changes

= 4.3.0 =
A lot of new changes

= 4.2.1 =
hot fix

= 4.2.0 =
Small bug fixes and new functionality arrived

= 4.1.2 =
* Taxonomy bug hot-fix
* Small fixes on showing restriction messages

= 4.1.0 =
Added allot of new functionality and fixed some issues

= 4.0.0 =
Added new functionality and fixed some issues

= 3.9.6 =
Added new functionality and fixed some issues

= 3.9.2 =
Small issues fix

= 3.9.0 =
A lot of new functionality and bug fixes