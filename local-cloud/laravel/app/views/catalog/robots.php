# robots.txt for http://www.tailwindapp.com/

# Begin sitemap
sitemap: http://www.tailwindapp.com/sitemap.xml
sitemap: http://blog.tailwindapp.com/post-sitemap.xml
sitemap: http://blog.tailwindapp.com/category-sitemap.xml
sitemap: http://blog.tailwindapp.com/post_tag-sitemap.xml

# End sitemap

#  PARTIAL access (Googlebot)
User-agent: Googlebot
Disallow: /*?
Disallow: /*.cgi$
Disallow: /*.css$
Disallow: /*.inc$
Disallow: /*.js$
Disallow: /*.gz$
Disallow: /*.php$
Disallow: /*rurl=*
Disallow: /*.txt$
Disallow: /*/trackback/$
Disallow: /*.wmv$
Disallow: /*.xhtml$

User-agent: Googlebot-Image
#Disallow: /wp-includes/

User-agent: Mediapartners-Google
Disallow: /

# digg mirror
User-agent: duggmirror
Disallow: /

# ia_archiver
User-agent: ia_archiver
Disallow: /

#  PARTIAL access (All Spiders)
User-agent: *


Allow: /sitemap.xml.gz$
Allow: /img/