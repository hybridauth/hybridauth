# Howto: Sign in with Apple

## Online documentation

https://developer.apple.com/sign-in-with-apple/get-started/
https://developer.okta.com/blog/2019/06/04/what-the-heck-is-sign-in-with-apple
https://sarunw.com/posts/sign-in-with-apple-2/

## Enable email delivery

Click on "More ..." and add domains and email addresses (requires SPF and DKIM, probably also an Apple ID in .well-known)

## Keys & IDs

### Identifiers

#### App ID

Create the primary ID for "Sign in".

#### Service ID

Create a service ID of the type *Sign in with Apple* and assign it to the app ID, then fill in your domains.

(Apple *Service ID* = OAuth2 *Client ID*)

### Key ID and private key

Create a new key for your Sign-In Service.
This gets you a key ID (under details) and the private key (download)

#### Attention:

* Don't forget to fill in the key name (there will be no error message if you forget).
* Downloading the privacy key is only possible once.

### Team ID

This is your Account ID at the top right of the account information (2nd line)

### Generate secret via script

See https://developer.okta.com/blog/2019/06/04/what-the-heck-is-sign-in-with-apple

1) Install jwt
2) Create the script client_secret.rb
3) `ruby ​​client_secret.rb`

"This JWT expires in 6 months, which is the maximum lifetime Apple will allow."

## Differences to other providers

`getUserProfile()` is not implemented, since Apple does not provide an API for that.

User information is **only** sent by Apple in the POST request as response to the first `authenticate()` call as a JSON Objekt in `$_POST['user']`. Make sure you save this information, there is no way to get it delivered a second time.

Different to Facebook and Google, Apple sends the code value as a **POST** request.

