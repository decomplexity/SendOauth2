# SMTP Scopes and Permissions for Microsoft OAuth2 #

Microsoft Oauth2 client scope with a URI of https://outlook.office.com (needed for SMTP AUTH or IMAP, for example) can be specified in a corresponding AAD permission but outlook.office.com is not listed in the resource API list!
Microsofts pushes Graph (which does have e.g. an SMTP AUTH permission), but have confirmed that SMTP AUTH is implemented only in the outlook resource API. 

Testing confirms this: if the client uses a scope of  https://graph.Microsoft.com/SMTP.Send and an AAD Microsoft Graph permission of SMTP.Send, authentication fails. The acid test lies in the ‘aud’ claim in the Access token: if it is “00000003-0000-0000-c000-000000000000” (aka Graph) authentication fails whereas an ‘aud’ of https://outlook.office.com will succeed (other things being equal).
One would have thought naively that the client scopes and AAD permissions resource API should have the same URI, because it isn’t obvious how granular permissions – where the AAD permissions are a superset of the client’s scopes and clients ask for the minimum they need at the time they need it – can work if I cannot specify the superset in AAD!

It is simple to prove these assertions. If AAD Graph permissions are null and the client scope is just https://outlook.office.com/SMTP.Send, the access token has an ‘aud’ of https://outlook.office.com and an ‘scp’ of SMTP.Send, then authentication is OK.
But if the client scope also contains a Graph scope such as Mail.Send, ‘aud’ changes to 00000003-0000-0000-c000-000000000000 (aka Graph) and subsequent authentication fails with ‘incorrect credentials’. One would expect a “scope asking for one token for two different resources” [which is illegal] type error message from the token endpoint.  And, as would be expected, if the client scope is just SMTP.Send (or https://graph.Microsoft.com/SMTP.Send; the Graph URI prefix being now the default) authentication fails with ‘incorrect credentials’
These are using the V2 authorisation and token endpoints – as recommended by Microsoft.

**To recap** : there isn’t an SMTP.Send listed under Exchange (API permissions => + Add a permission => Request API permissions /Microsoft APIs => Exchange or under Office 365 Exchange Online (API permissions => + Add a permission => Request API permissions / APIs my organization uses => Office 365 Exchange Online) . They are only listed under Graph. This leads to the weird situations that:
- if a client uses (e.g.) SMTP.Send (or https://graph.Microsoft.com/SMTP.Send) scope, authentication fails even though Microsoft instructs that the corresponding permission must be registered in Graph
- if a client uses (e.g.) https://outlook.office.com/SMTP.Send scope (again as instructed by Microsoft), the client cannot add further https://outlook.office.com permissions (because the resource API is not listed) or use additional Graph scopes (because authentication fails and it would appear to be asking for one token to cover two APIs anyway)
Microsoft has commented that, “We see that the permissions are under Microsoft Graph in the Azure portal, but in fact the same has been added to the outlook endpoint”. However,  Microsoft could pre-empt much confusion if it simply listed the outlook.office.com API in AAD API permissions until Graph accessed a complete set of endpoints as well as permissions. The present situation is akin to being half-pregnant!


If your implementation refuses to authenticate, it is well worth reading: ‘Basic Authentication and Exchange Online – July update’ in the Microsoft Exchange Team blog (in https://techcommunity.microsoft.com). 
