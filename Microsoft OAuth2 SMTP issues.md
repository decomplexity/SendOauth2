# Microsoft OAuth2 SMTP issues #

## Purpose ##
This note describes authentication and authorization problems implementers may encounter and some solutions or workarounds. It does not cover the coding of authorization flows: these are dealt with elsewhere in framework-specific (e.g. Laravel with Passport) or ‘provider’-specific (e.g. thenetworg/oauth2-azure or greew/oauth2-azure-provider) documentation, and their developers may well have provided tested and useful defaults - for scope permissions especially.
 


## Findings ##
The deprecation of the Outlook REST V2.0 and beta APIs (‘Outlook API’) has given both developers and Microsoft itself a headache since it has been a common interface to Exchange Online (‘EXO’). Outlook API has intentionally not been listed as a selectable resource API in Azure Active Directory (‘AAD’) even in the early months of the deprecation window, as Microsoft wanted developers to use Graph and the Microsoft Authentication Library (‘MSAL’) that provide alternative ways to manage mail. 
Microsoft has stated, however, that despite the retirement of the Outlook API per se, certain API calls will continue to be available indirectly, notably those from IMAP, POP and SMTP.

However, Microsoft’s planned decommissioning of Outlook API created some weird side-effects, the most obvious - and most confusing and surprising to developers - being that:

1. the order of the scope operands in a scope permissions statement became significant for issuing tokens. This is especially salient as the order can determine to which API (e.g. Graph or Outlook API) the scope permissions statement applies. AAD selects scope operands in sequence and, ignoring any such as offline_access that are directives and not permissions, decides from the operand’s URI identity prefix (or lack of it) whether or not it is a Graph permission. If the selected permission is a Graph permission, subsequent permissions will also be construed to be Graph permissions if they are valid Graph permissions (they may, for example, also be valid Outlook API permissions). And this will then cause SMTP authentication to fail because EXO will check the token and bounce it as “Whoops, this is for Graph and not for me” (see ‘The aud claim’ later). 
               

2. Microsoft’s statement that “if a scope permission does not have a resource identifier (URI prefix), that permission is assumed to be a Graph permission” is wrong when that scope permission is preceded in the scope list by a URI-prefixed permission that is for a non-Graph resource API such as the Outlook API. To elaborate further:

> a. if a URI-prefixed scope permission for a non-Graph resource precedes a non-prefixed permission for a Graph resource AND both permissions are valid for their respective resources (e.g.  https://outlook.office.com/SMTP.Send Mail.Send), then the latter (Mail.Send) is assumed to be a permission for the non-Graph resource.

> b. if a non-URI-prefixed permission for a Graph resource precedes a URI-prefixed permission for a non-Graph resource AND both permissions are valid for their respective resources (e.g. Mail.Send https://outlook.office.com/SMTP.Send), the latter is assumed to be a permission for the Graph resource (the https://outlook.office.com/ is overridden).


> In 2a and 2b above, is it assumed that the ‘preceding resource’ is not itself preceded by a Graph resource. *What matters is which scope permission comes first*. 


3. An authorization endpoint can issue an authorization code for more than one resource API, but a token endpoint can only issue a token for one resource API. In addition, however, when scope operands are valid for more than one resource API (e.g. Mail.Read and SMTP.Send, which are valid scopes for both Graph and Outlook API), AAD will consider the *first-mentioned scope permission* to define the token audience (the token ‘aud’ claim) instead of throwing an AADSTS28000 Invalid Multiple Resources Scope exception.  
 

4. A scope permission of https://outlook.office.com/SMTP.Send is linked with a Graph registration permission of SMTP.Send (whether added manually in advance as a delegated permission to AAD or via on-the-fly consent by User Principal). *But Graph does not itself have SMTP support: its registration permission of SMTP.Send is used internally as a ‘proxy’ for an outlook.office.com permission* since Outlook API is not itself selectable as a resource API in AAD. If Graph did indeed support SMTP natively, the SMTP server endpoint called by the client app could not be smtp.office365.com (which is EXO) but smtp.graph.microsoft.com or something similar.    
This leads to the bizarre situation that a scope permission of SMTP.Send is invalid (unless preceded by an Outlook permission – vide 2.) when an AAD registration permission SMTP.Send is not only valid but mandatory.      


5. The foregoing findings refer to authorization_code grant type flows. Dedicated mail-out ‘demon’ applications such as PHPMailer that are frequently used with delegated permissions from User Principal and do not need the end-user to log in would run more efficiently with client_credentials grant as no refresh token or scope permissions are used: scope permissions default to the AAD registration permissions. 
Authorization_code flow support for IMAP, POP and SMTP has been available since April 2020. Client_credentials support for IMAP and POP was announced in August 2022 but SMTP was a notable absentee. Whether this absence is to pre-empt a possible open-relay security exposure is unclear. 


6. A final and common reason for SMTP failing at authorization with ‘535 5.7.3 Authentication unsuccessful’ is the email account itself simply not having SMTP AUTH allowed in EXO Admin Center => Active Users => select user => Mail tab => Manage email apps


## TOKEN ISSUE – EXAMPLES ##
These examples assume authorization_code grant flow with optional issue of a refresh token (hence the offline_access directive).

a. a scope of “offline_access https://outlook.office.com/SMTP.Send Mail.Read” will successfully create Refresh and Access tokens for EXO resource, and SMTP authenticates OK
This is interpreted as: offline_access  https://outlook.office.com/SMTP.Send  https://outlook.office.com/Mail.Read


b. a scope of “offline_access Mail.Read https://outlook.office.com/SMTP.Send” will successfully create Refresh and Access tokens but fail on SMTP authentication
This is interpreted as offline_access https://graph.microsoft.com/Mail.Read https://graph.microsoft.com/SMTP.Send.
It fails SMTP authentication because the token ‘aud’ claim is Graph and not Outlook. (Why the earlier creation of Refresh and Access tokens succeeds, since Graph does not itself support SMTP, is unclear.) 

     

## COMMENTARY ##
There isn’t an SMTP.Send listed in AAD under Exchange (API permissions => + Add a permission => Request API permissions / Microsoft APIs => Exchange or under Office 365 Exchange Online (API permissions => + Add a permission => Request API permissions / APIs my organization uses => Office 365 Exchange Online): SMTP.Send is only listed under Graph. This leads to the odd state of affairs that if a client uses (e.g.) https://outlook.office.com/SMTP.Send scope, the client cannot add further https://outlook.office.com registration permissions (because the Outlook resource API is not listed by AAD, and Graph almost certainly won’t provide ‘proxy’ registration permissions for them as it does for SMTP, IMAP and POP) or use additional Graph scopes (because authentication fails, and it would appear to be asking for one token to cover two APIs anyway)
   
Microsoft has commented that, “We see that the permissions are under Microsoft Graph in the Azure portal, but in fact the same has been added to the outlook endpoint”. However, Microsoft could have pre-empted much confusion if it simply listed the outlook.office.com API in AAD API resource list, but this would vitiate the incentive for developers to use MSAL and Graph. The present situation is akin to being half-pregnant!
Microsoft originally said that SMTP AUTH would not be included in Graph - and hence in its registration permissions list - but only in EXO. But SMTP AUTH then unexpectedly appeared in Graph AAD registration permissions, and an enable/disable switch added to the tenant (accessible via Powershell) which could be overridden at user level (accessible via a pulldown in the user account). What was not widely publicised was that the default for new tenants was ‘disabled’, and this is at the protocol level so did not just affect OAuth2.  
 
Apparently less well known was that access tokens issued for the Graph API were V1 tokens even though they were requested from a V2 endpoint (V2 endpoints can issue V1 tokens and vice versa.) because access tokens are always of the type (version) appropriate for the API, so you cannot mix API types in the same client scopes list. 
*However this is not true for ID tokens which always conform to the endpoint version that issued them.*
 
Finally, the access token scopes list must not – after AAD’s own changes to resource identities as in 2a and 2b - span APIs anyway, so the client code needs to request tokens one by one. Since authorization codes cannot be reused, the common workaround appears to be to use the refresh token (acquired along with the first access token by specifying offline_access) to request a new access token for the second API, and so on.

## DIAGNOSTICS ##

### The ‘aud’ claim ###

If the client uses a single scope permission of SMTP.Send (or https://graph.microsoft.com/SMTP.Send) and an AAD Graph registration permission of SMTP.Send, authentication fails. *The acid test lies in the ‘aud’ claim in the Access token*: if it is “00000003-0000-0000-c000-000000000000” (aka Graph) authentication fails, whereas an ‘aud’ of https://outlook.office.com will succeed (other things being equal).
One would have thought naively that the client scopes and AAD permissions resource API should have the same URI, because it isn’t obvious how granular permissions – where the AAD permissions are a superset of the client’s scopes, and clients should ask for the minimum they need at the time they need it – can work if the superset cannot be specified in AAD!

It is simple to prove these assertions. If AAD Graph permissions are null and the client scope is just https://outlook.office.com/SMTP.Send, the access token (decoded with jwt.io or jwt.ms for example) has an ‘aud’ of https://outlook.office.com and an ‘scp’ of SMTP.Send, then authentication is OK.
But if the client scope also contains a Graph scope such as Mail.Send that precedes https://outlook.office.com/SMTP.Send, ‘aud’ changes to 00000003-0000-0000-c000-000000000000 (aka Graph) and subsequent SMTP authentication fails with ‘535 5.7.3 Authentication unsuccessful’. One would expect an ‘AADSTS28000 Invalid Multiple Resources Scope exception’ error message from the token endpoint.  

These assertions were tested are using the V2 authorisation and token endpoints.

### Token scope ###

Although the scope permissions within an access token can be displayed by decoding with e.g. jwt.io, those within a refresh tokens cannot. But these scopes can be displayed from the AAD sign-in log in a slightly unobvious place as follows:

- sign in to AAD
- select Enterprise Applications 
- select your app
- select Sign-in Logs (near the bottom of the vertical list)
- select User-sign-ins (non-interactive)
- select Refresh
- select the Request ID ‘Aggregate’ item for today’s date and for Resource ‘Office 365 Exchange Online’, 'Graph' or other request you wish to check
- select the event for the time of your last failed event or other event in which you are interested 
- *select the ‘…’ on the RHS of the title*
- see the Oauth Scope Info field in the Additional Details tab



## KEY DATES ##

OAuth2 authentication and authorization was enabled by default for all new Microsoft 365 tenants from August 2017, and from October 2019 many new tenants had *Security Defaults* in Azure Active Directory (AAD) enabled by default. Among other things, Security Defaults blocks Basic Authentication and forces multi-factor authentication across the tenant. 

In July 2020 Microsoft announced that it would disable SMTP, IMAP and other protocols using Basic Authentication for all tenants that weren't using them and introduced a simpler tenant-wide option for Admin to manage this (Settings > Org Settings > Modern Authentication). Microsoft noted at the time that this was complementary to control through Exchange profiles via Powershell and that to some extent they covered the same ground but with the latter giving greater granularity such as control at user rather than tenant level.

Microsoft also confirmed that disabling SMTP, IMAP and other protocols using basic authentication for EXO tenants that were using them would be enforced in the second half of 2021. But in February 2021, Microsoft decommitted to this enforcement, merely saying that it was still in the Roadmap but would give twelve months’ notice of enforcement. 

EXO Support for SMTP, IMAP and POP authentication with OIDC and OAuth2 was introduced in April 2020. 

The Outlook REST API V2.0 and beta endpoints were scheduled to be killed off in November 2022 but were at the last moment (23rd November) given a temporary stay of execution. Microsoft said that “the target for the new deprecation date will still be in 2023. We will provide a 6 month notice period before enforcing the block on the endpoint”.
However, the related scope permissions such as https://outlook.office.com/SMTP.Send are not deprecated and will continue to work. In OAuth terms, scopes are part of the authorisation process and used by the authorization endpoint to create an authorization code that is then exchanged for an access token. Direct access (e.g. read / write) to the resource server API is a different matter, although – like SMTP – special provision is made for continued use of IMAP and POP.



Acknowledgements: to Microsoft’s Identity Authentication developer support team in Lisbon, the Graph developer support team in India and personal contacts elsewhere in Microsoft for help. 





 
