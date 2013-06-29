<!doctype html>
<html>
<head>
<body>
<p>Goal</p>
<ul>
<li>Host a web application as a sample for my resume </li>
<li>Education, entertainment, collaboration, expert systems</li>
<li>Multimedia gallery of animal information, an emphasis on birds</li>
<li>Interactive as to the classification and labeling of media</li>
<li>Machine and people learn to more accurately identify clips</li>
<li>Promote conservation, species awareness and preservation</li></ul>
<p >Schema</p>
<p >An upload is media (sound, text, image, video) submitted from the internet. 
An upload is always associated with a contact. A contact is an individual 
living creature. Contacts can be followed by anyone and will be purged from 
the site after a long period of inactivity. Contacts can be assigned a partial 
or complete taxonomic classification.</p>
<p >Privacy</p>
<p >The site maintains no user accounts, everything is open to
everyone. The protocol is unsecure so the connection is unencrypted. Consider
everything you send or receive from the site as open to the public. While
browsing the site a temporary session is maintained with a cookie or URL
rewriting. The normal information your browser sends across the internet (IP
address, time, headers, etc.) will be used for the purposes of running the
site. The site will never ask for personal information.</p>
<p >Security</p>
<p >Modern browsers do a good job of keeping your computer safe
and stable. For protection the site prohibits the upload of executable, script,
and system files. This site like any on the internet is use at your own risk.</p>
<p >Todo</p>
<ul>
<li>Convert uploaded audio files to WAV, run pitch trace, and compare</li>
<li>Classify contacts</li>
<li>Lots more...</li>
</ul>
<p >Source</p>
<ul>
<li><a href="https://github.com/bshaw1975/chirp">GitHub Repository<a></li>
</ul>
<p >Credits</p>
<ul>
<li>Audio pitch tracking "chirp" written by Daniel Meliza<br/><a href="https://github.com/dmeliza">https://github.com/dmeliza</a></li>
<li>The server runs a basic LAMP stack<br/><a href="http://en.wikipedia.org/wiki/LAMP_(software_bundle)">http://en.wikipedia.org/wiki/LAMP_(software_bundle)</a></li>
<li>The client is HTML and Javascript with a boost from the Yahoo UI<br/><a href="http://yuilibrary.com">http://yuilibrary.com</a></li>
<li>Microphone is based on the WAMI recorder<br/><a href="http://code.google.com/p/wami-recorder">http://code.google.com/p/wami-recorder</a></li>
</ul>
<p >The microphone recorder component saves data to the web server with HTTP POST requests. 
The format of the audio data is <a href="http://wiki.multimedia.cx/index.php?title=PCM">wav pcm s16 le 22050 hz mono</a>
That may be familiar to anyone who’s done any digital sound editing. Lets explain each part of the format:</p>
<ul>
<li>PCM pulse-code modulation</li>
<li>S16 signed 16-bit integer sample (2 byte)</li>
<li>LE byte order is little-endian (pronounced indian) </li>
<li>22k hertz 22 thousand samples are taken every second</li>
<li>mono single channel</li>
</ul>
</body>
</html>