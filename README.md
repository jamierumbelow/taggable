# Taggable

Taggable is a formerly commercial ExpressionEngine 2 tagging add-on. It's been a popular solution for tagging in EE2 since day one; it was the first EE2 compatible tagging system. Taggable is an easy-to use, powerful and advanced tag module/add-on designed exclusively for EE. It is compatible, flexible and sophisticated.

After much deliberation, I decided to retire my add-ons at [Sparkplugs](http://getsparkplugs.com). I'm open sourcing Taggable under the MIT license for the community so that it can remain future-version compatibile and hopefully it will develop and grow into a bigger and better add-on.

## Synopsis

    {exp:channel:entries channel="blog" orderby="date" sort="desc"}
        <h1>{title}</h1>
        <p>Tags: {post_tags backspace="2"}{name}, {/post_tags}</p>

        {content}
    {/exp:channel:entries}

## Installation

Install Taggable like any other ExpressionEngine 2 addon. Simply drag the _taggable/_ folder found in your download into the _system/expressionengine/third_party/_ folder. Then go into the EE control panel, navigate to the **Modules** page and find Taggable in the list.

Click the install link and you should see a set of form fields allowing you to enable or disable the various parts of the addon. Make sure you enable every part of the addon - the _module_ and _fieldtype_.

And you've installed Taggable!

## Usage

Complete usage documentation is available in the repo. I want to convert it all over to Markdown and chuck in this repo's wiki.

## Changelog
**Version 1.4.7**
* Fixed residual references to $license_key bug

**Version 1.4.6**
* Fixing a styling conflict with Playa 
* Ensuring that tags are trimmed appropriately
* ExpressionEngine 2.2.2 compatibility
* Changed the structure of the UI to enable number-only tags

**Version 1.4.5**
* Adding an :entries tag
* Improving backend UI behaviour
* Fixed an error with size calculation 
* Cleared up code and output
* Fixed an error with using multiple taggable module tags on a page
* Adding DB selection check to import 
* Made a slight change to the tag cloud functionality, dramatically improving the spread across tag sizes
* Fixed some errors in the CP and module tags

**Version 1.4.2**
* Fixed a CP error
* Added the {exp:taggable:url_name} template tag
* Fixed entry versioning compatibility
* Removed support for the old SAEF
* Removed the Spanish language pack
* Fixed an error with SafeCracker

**Version 1.4.1**
* Added the field parameter
* Added the limit="" parameter to the fieldtype
* Fixed a quick bug with deleting tags entry counts
* You can now order by the entry count
* Added the channel="" parameter finally
* Fixed a bug with the backspace="" parameter
* Added multiple fields to field="" parameter
* Fixed an issue with the {size} variable

**Version 1.4.0**
* Added Pixel and Tonic Matrix support
* Added Low Variables support
* Added a range of themes and themeable inputs
* Added SafeCracker support
* Performance boosts
* Separation of module and fieldtype
* Added :ol and :ul tags to fieldtype
* Added the Taggable API
* Bug and compatibility fixes

**Version 1.3.3**
* Fixed an error with tag clouds

**Version 1.3.2**
* Fixed a bug with the module tag
* Removed 1.2-1.3 upgrade compatibility

**Version 1.3.1**
* Fixed a problem with the updater
* Fixed an issue with running under FireFox
* Fixed a bug with a faulty SQL query

**Version 1.3.0**
* Reworked the internal tag-storing
* Removed nearly all the module tags and moved everything into fieldtype
* Added support for search:tags
* Added per-fieldtype settings
* Added tag indexing
* Added PHP5.1 support
* Fixed many many bugs
* Enhanced SAEF support

**Version 1.2.1**
* Fixed bug where new tags weren't saving correctly
* Fixed bug with masked CP access
* Added exp_channel_data support to upgrader
* Updated license URLs
* Completed language pack

**Version 1.2.0**
* Taggable Custom Field Support
* Removal of tags tab
* Enhanced entries tag
* NSM Addon Updater Support
* SAEF custom field support
* Import and Export Tool
* Diagnostics Tool
* Cleaner and refactored code
* Bugfixes!

**Version 1.1.0**
* Support for Multi-site Manager
* Channel filtering in all tags
* An {if no_tags} tag for the channel entries {tags} tag
* Spanish Language Pack
* Bugfixes

**Version 1.0.0**
* Initial Release
