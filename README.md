# rainloop-plugin-http-password
Rainloop plugin for changing password via cutomized URL with parameters substitution. 
The actual password changing process involve posting 2 HTTP requests, so you are free to
separate the information on 2 different URL's. 

Place holder parameters are:
%USER% - email user ID without the domain name
%DOMAIN% - email domain name
%OLDPASSWORD% - existing password
%NEWPASSWORD% - proposed new password submitted

All URL are expected to return XML.

1) URL for allowing password change:
Only user ID and domain name are passed. No password is send on this URL. 
Expecting an xml with an entry <enabled>true|false</enabled>

e.g. https://mail.example.com/permission?user=%USER%&domain=%DOMAIN%

    <?xml version="1.0" encoding="utf-8"?>
    <content>
    <enabled>true</enabled>
    </content>

2) URL for changing password first pass:
All information are passed including %USER%, %DOMAIN%, %OLDPASSWORD%, and %NEWPASSWORD%.
The entries in the XML content returned with be attached the the second URL.

e.g. https://mail.example.com/PasswordChangePass1?user=%USER%&domain=%DOMAIN%&oldpassword=%OLDPASSWORD%

    <?xml version="1.0" encoding="utf-8"?>
    <content>
    <token>12345</token>
    </content>

3) URL for changing password second pass:
All information are passed including %USER%, %DOMAIN%, %OLDPASSWORD%, and %NEWPASSWORD%.
Expecting an xml with and entry <result>true|false<result>
When result = false, assumed the password changing process failed.

e.g. https://mail.example.com/PasswordChangePass1?user=%USER%&domain=%DOMAIN%&newpassword=%OLDPASSWORD%&token=12345

    <?xml version="1.0" encoding="utf-8"?>
    <content>
    <result>true</result>
    </content>
