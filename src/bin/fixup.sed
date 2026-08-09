s/\&lt;/\</g
s/\&gt;/>/g
#s/\$\([A-Z]\)/\1/g
s;<h1 ;<hr/><hr/><h1 ;g
s;<h2 ;<hr/><h2 ;g
s/<p>\(<blockquote[^>]*>\)/\1<p>/
s;</blockquote></p>;</p></blockquote>;
s/\&quot;/'/g
