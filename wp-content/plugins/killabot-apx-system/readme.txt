=== The Killabot APx System (Anonymous Proxy Detection) ===
Contributors: mark.patterson
Tags: spam,anonymous,proxy
Requires at least: 2.5.2
Tested up to: 2.8.3
Stable tag: 1.0.5

Plugin for detecting and blocking access to your Site from Anonymous Proxy users.

== Description ==

Software that turns a website into an Anonymous Proxy Server is freely available 
for anyone to download. Every day new websites, with unrecorded IP Addresses, 
are popping up as new Anonymous Proxy Sites. Increased popularity, easy 
accessibility and the protection of anonymity has led to an increase 
in suspicious online activity.

An algorithm has been developed as an effective solution to the complex Proxy dilemma. 
This system's results produce the most accurate way to identify anonymous proxies 
by looking through their complex characteristics. This sharply contrasts with
methods which rely primarily on using IP blacklists of yesterday's proxies.

== Installation ==

1. Upload the 'killabot-apx' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select 'Options Configuration Panel' from the `Manage Plugins` page.
4. The installation wizard will ask you to 'Register API Key'. This step is mandatory.
5. Once the key is registered, the wizard will continue the installation process.
6. The wizard will finalize the installation and run an anonymous proxy test to confirm proper installation.

== Frequently Asked Questions ==

= How does this Plugin block Anonymous proxies from my WordPress Site? =

The Killabot APx System has been designed to detect certain characteristics common amongst anonymizing proxies. Their signatures are sometimes hard to detect but there is an algoritm that can return results with a very high degree of accuracy. 

= Are all pages on my Site protected by this Plugin? =

No, this Plugin only protects pages where information is actually exchanged. For instance, login and comment form submissions are protected while information-only type pages are left alone. 

== Screenshots ==

[http://www.killabot.net/images/plugin.jpg  Register API Key]

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 1.0.5 =
* List versions from most recent at top to oldest at bottom.