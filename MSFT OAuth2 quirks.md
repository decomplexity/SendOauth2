# Scopes & Permissions for Microsoft OAuth2 SMTP #

## Purpose ##
This note summarises the main authentication problems encountered and their solutions or workarounds used during the implementation of SendOauth2.   


## Some key dates ##
OAuth2 authentication and authorization was enabled by default for all new Microsoft 365 tenants from August 2017, and from October 2019 many new tenants had *Security Defaults*  in Azure Active Directory (AAD) enabled by default. Among other things, Security Defaults blocks Basic Authentication and forces multi-factor authentication across the tenant. 

In July 2020 Microsoft annouced that it would disable SMTP, IMAP and other protocols using basic authentication for all tenants that weren't using them and introduced a simpler tenant-wide option for Admin to manage this (Settings > Org Settings > Modern Authentication). Microsoft noted at the time that this was complementary to control through Exchange profiles via Powershell and that to some extent they covered the same ground but with the latter giving greater granularity such as control at user rather than tenant level.

Microsoft also confirmed that disabling SMTP, IMAP and other protocols using basic authentication for tenants that *were*  using them would be enforced in the second half of  2021. But in February 2021, Microsoft decommitted to this enforcement, merely saying that it was still in the Roadmap but would give twelve months notice of enforcement. 


Microsoft 365 Support for SMTP and IMAP authentication with OIDC and OAuth2 was introduced in April 2020 

However, Microsoft's implementation of SMTP OAuth2 with permissions specified in Graph was confusing. The SendOauth2 wrapper takes care of most of these quirks, but diagnosing authentication problems can be difficult and cause some surprises! - as will now be outlined.      


## MSFT's SMTP OAuth2  implementation ## 
Microsoft Oauth2 client scope with a URI of https://outlook.office.com (needed for SMTP AUTH or IMAP, for example) can be specified in a corresponding AAD permission but outlook.office.com is not listed in the resource API list!
Microsofts pushes Graph (which does have e.g. an SMTP AUTH permission), but have confirmed that SMTP AUTH is implemented in the outlook resource API. 

Testing confirms this: if the client uses a scope of  https://graph.Microsoft.com/SMTP.Send and an AAD Microsoft Graph permission of SMTP.Send, authentication fails. The acid test lies in the ‘aud’ claim in the Access token: if it is “00000003-0000-0000-c000-000000000000” (aka Graph) authentication fails whereas an ‘aud’ of https://outlook.office.com will succeed (other things being equal).
One would have thought naively that the client scopes and AAD permissions resource API should have the same URI, because it isn’t obvious how granular permissions – where the AAD permissions are a superset of the client’s scopes and clients ask for the minimum they need at the time they need it – can work if the superset cannot be specified in AAD!

It is simple to prove these assertions. If AAD Graph permissions are null and the client scope is just https://outlook.office.com/SMTP.Send, the access token (decoded with jwt.ms for example) has an ‘aud’ of https://outlook.office.com and an ‘scp’ of SMTP.Send, then authentication is OK.
But if the client scope also contains a Graph scope such as Mail.Send, ‘aud’ changes to 00000003-0000-0000-c000-000000000000 (aka Graph) and subsequent authentication fails with ‘incorrect credentials’. One would expect a “scope asking for one token for two different resources” (which is illegal) type error message from the token endpoint.  And, as would be expected, if the client scope is just SMTP.Send (or https://graph.Microsoft.com/SMTP.Send; the Graph URI prefix being now the default) authentication fails with ‘incorrect credentials’.
These are using the V2 authorisation and token endpoints – as recommended by Microsoft.

**To recap** : there isn’t an SMTP.Send listed under Exchange (API permissions => + Add a permission => Request API permissions /Microsoft APIs => Exchange or under Office 365 Exchange Online (API permissions => + Add a permission => Request API permissions / APIs my organization uses => Office 365 Exchange Online) . They are only listed under Graph. This leads to the weird situations that:
- if a client uses (e.g.) SMTP.Send (or https://graph.microsoft.com/SMTP.Send) scope, authentication fails even though Microsoft instructs that the corresponding permission must be registered in Graph
- if a client uses (e.g.) https://outlook.office.com/SMTP.Send scope (again as instructed by Microsoft), the client cannot add further https://outlook.office.com permissions (because the resource API is not listed) or use additional Graph scopes (because authentication fails and it would appear to be asking for one token to cover two APIs anyway)
- the Outlook REST API (version 2) endpoint will be decomissioned in November 2022, but the related scopes such as https://outlook.office.com/SMTP.Send will continue to work. In OAuth terms, scopes are part of the authorisation process and used by the authorization endpoint to create an authorization code that is then exchanged for an access token. Direct access (read / write etc) to the resource server API is different, although since the resource server should check that the scopes in an access token are acceptable, this still doesn't explain how a different resource server API (i.e. not Outlook REST) should cope with them.
   
Microsoft has commented that, “We see that the permissions are under Microsoft Graph in the Azure portal, but in fact the same has been added to the outlook endpoint”. However,  Microsoft could pre-empt much confusion if it simply listed the outlook.office.com API in AAD API permissions until Graph accessed a complete set of endpoints as well as permissions. The present situation is akin to being half-pregnant!


MSFT originally said that SMTP AUTH would not be included in Graph - and hence in its permissions list - but only in Office365. But it then unexpectedly  appeared in Graph, and an enable/disable switch added to the tenant (accessible via Powershell) which could be overridden at user level (accessible via a pulldown in the user account). What was not widely publicised was that the default for new tenants was ‘disabled’, and this is at the protocol level so did not just affect OAuth2.  
 
Apparently less well known was that access tokens issued for the Graph API were V1 tokens even though they were requested from a V2 endpoint (V2 endpoints can issue V1 tokens and vice versa.) because access tokens are always of the type (version) appropriate for the API so you cannot mix API types in the same client scopes list. 
*However this is not true for ID tokens which always conform to the endpoint version that issued them.*
 
Finally, the access token scopes list must not span APIs anyway, so the client code needs to request tokens one by one. Since authorization codes cannot be reused, the common workaround appears to be to use the refresh token (acquired along with the first access token by specifying offline_access) to request a new access token for the second API, and so on.





If your implementation refuses to authenticate, it is well worth reading: ‘Basic Authentication and Exchange Online – July update’ in the Microsoft Exchange Team blog (in https://techcommunity.microsoft.com). 
