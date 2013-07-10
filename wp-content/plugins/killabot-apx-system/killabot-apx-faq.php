<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Killabot WordPress Pkugin &rsaquo; FAQ's</title>
	<style type="text/css">
		body {
			background:#f7f6f1;
		}
		#header {
			background:#1d507d;
			height:45px;
		}
		a {
			font-family:arial;
			font-size:12px;
			}
		ol {
			font-family:arial;
			font-size:12px;
			}
			
		p {
			text-align:justify;
			font-family:arial;
			font-size:12px;
		}
		.header {
			background:#5782a8;
			color:#efefef;
			width:500px;
			margin-left:10px;
			font-family:arial;
			font-size:12px;
			font-weight:bold;
		}
	</style>	
</head>
<body>
<div id="header"><img src="images/k-logo.gif" alt="K Logo"/></div>
<p/>
<a name="top"/>

<div class="header">&nbsp;General Questions</div>
<ol>
<li><a href="#q1">What is an Anonymous Proxy?</a></li>
<li><a href="#q2">What are the dangers to a webite or blog operator from Anonymous Proxies?</a></li>
<li><a href="#q3">What is SaaS and how is it used in this Plugin?</a></li>
<li><a href="#q4">Does this system affect major Search Engine robots?</a></li>
</ol>	


<div class="header">&nbsp;Plugin Questions</div>
<ol>
<li><a href="#q5">How do I install The Killabot APx Plugin?</a></li>
<li><a href="#q6">What is the Registration Key and why do I need to register it?</a></li>
<li><a href="#q7">How does this Plugin block Anonymous proxies from my WordPress Site?</a></li>
<li><a href="#q8">Are all pages on my Site protected by this Plugin?</a></li>
<li><a href="#q9">What is the "Anonymous Proxy Test button?</a></li>

</ol>
	
<p/>
<p><a name="q1"/>
<b>What is an Anonymous Proxy?</b><br/> 
An anonymous proxy server (sometimes called a web proxy) generally attempts 
to anonymize web surfing. There are different varieties of anonymizers. 
One of the more common variations is the open proxy. Because they are typically 
difficult to track, open proxies are especially useful to those seeking online 
anonymity, from political dissidents to computer criminals. </p><p>Some users are 
merely interested in anonymity on principle, to facilitate constitutional human 
rights of freedom of speech, for instance. The server receives requests from the 
anonymizing proxy server, and thus does not receive information about the end 
user's address. However, the requests are not anonymous to the anonymizing 
proxy server, and so a degree of trust is present between that server and the 
user. Many of them are funded through a continued advertising link to the user.
<br /><a href="#top">Top</a></p>

<p/>

<p><a name="q2"/>
<b>What are the dangers to a webite or blog operator from Anonymous Proxies?</b><br/> 
With the ability to conceal their true identity proxy users have greatly lowered 
their risk of being caught, which in turn has fostered a culture of dangerous 
behavior. Malicious proxy users can anonymously hack into networks through web 
portals and potentially cost the victim company millions of dollars in: stolen 
funds; lost trade secrets and research; repair costs; and an overall decline in 
consumer faith in the company. Additionally, anonymous proxies users can create 
spam email accounts, spam blogs and execute numerous other damaging attacks 
that have the ability to become very costly to correct.
<br /><a href="#top">Top</a></p>

<p/>


<p><a name="q3"/>
<b>What is SaaS and how is it used in this Plugin?</b><br/> 
Software as a Service (SaaS) is a software delivery method that provides access 
to software and its functions remotely as a Web-based service. Also, because 
the software is hosted remotely, users don't need to invest in additional 
hardware. SaaS removes the need for organizations to handle the installation, 
set-up and often daily upkeep and maintenance.</p>
<p>This Plugin is a web service, inspecting incoming web requests 
[form submissions] and detects a variety of dangerous parameters that 
could negatively affect a web server's performance or security. The web service 
transport layer is accomplished via XML-RPC.
<br /><a href="#top">Top</a></p>

<p/>

<p><a name="q4"/>
<b>Does this Plugin affect major Search Engine robots?</b><br/> 
No, because this Plugin only protects form submissions (search engine
robots don't submit information) there will be no affect when they come 
crawling.
<br /><a href="#top">Top</a></p>

<p/>


<p><a name="q5"/>
<b>How do I install The Killabot APx Plugin?</b><br/> 
Here a link to the complete installation instructions.
<a href="http://www.killabot.net/?page_id=17" target="blank">Installation Instructions</a>
<br /><a href="#top">Top</a></p>

<p/>

<p><a name="q6"/>
<b>What is the Registration Key and why do I need to register it?</b><br/> 
The Registration Key is a unique number assigned to your particular domain. This 
key is necessary for the Web Service to work correctly.<br/>
<img src="images/faq-register.gif" alt="Registration Key"/><br/>
Once registered, the system will be able to match the Domain with the IP
Address to prevent fraud.
<br /><a href="#top">Top</a></p>

<p/>

<p><a name="q7"/>
<b>How does this Plugin block Anonymous proxies from my WordPress Site?</b><br/> 
The Killabot APx System has been designed to detect certain characteristics 
common amongst anonymizing proxies. Their signatures are sometimes hard to detect
but there is an algoritm that can return results with a very high degree 
of accuracy.
<br /><a href="#top">Top</a></p>

<p/>


<p><a name="q8"/>
<b>Are all pages on my Site protected by this Plugin?</b><br/> 
No, this Plugin only protects pages where information is actually exchanged.
For instance, login and comment form submissions are protected while information-only
type pages are left alone.
<br /><a href="#top">Top</a></p>

<p/>

<p><a name="q9"/>
<b>What is the "Anonymous Proxy Test button?</b><br/> 
This button is for Site Admins to test the functionality of the System. Pressing
the button makes an actual Post [form submission] to the <i>wp-login.php</i> page.
The test is a live anonymous proxy Post to see if the system is properly installed.
<br /><a href="#top">Top</a></p>



</body>
</html>
