Request format
===

All request are POST with a Content-Type of `application/json` and a JSON body having the following attributes:

* `token`: (string) required for all authenticated requests (that is all requests, except `device/register`)
* `data`: (mixed) put here the data required by the API method you call. Most probably an object. Can be omitted for
    methods that do not require additional input data.
* `businessVersion`: (string) the last `businessVersion` that you received. See "businessVersion" section below. Always
    put a `businessVersion` if you received one in your last request. Omit this attribute only if you don't have it.

Also, all API request are relative to a "business". This is determine by the URL. Ex:
`https://{my-business-slug}.example.com/api`.

Response format
===

All response will be JSON object with the following attributes :

* `status`: (string) either `"ok"` or `"error"`
* `data`: (mixed) data returned by this API method. Can be not present if the method doesn't return data or in case of error.
* `businessVersion`: (string) current business data version (see "businessVersion" section below).
* `error`: (object) Present only if `status` is `"error"`. Contains the following keys: `code`: (string) error code; and
    `message`: (string) optional message giving more details for the error. This error message will never contain sensitive
    information, but is probably too technical to show the end user. It is also always in English.
* `upToDateBusinessData`: (object) If the server judged that the device needs updated business data (generally because
    of an outdated `businessVersion`), this object will contain the updated data. It will probably not contain the whole
    Business instance (though it is possible) but only the parts that need to be replaced (ex: `products`).
* `token`: (string) if the device is correctly authenticated, contains the token to send in the next request. It can be
    different than the one used in the current request, so always use in your requests the one received in the last
    response. Note that if you make a request and an error is returned, the error will probably not have a `token`. For
    your next request, use the last token you received.
    
General error codes
---

The following error codes can be returned by (almost) all API methods (in the `error.code` JSON attribute):

* `auth:failed`: For all API methods that require a valid token.
* `request:invalid`: when trying to make an invalid request to the API. Either wrong JSON structure or because passed
    data is missing required attributes or their value is invalid.
* `request:notFound`: when requesting a non-existing API method
* `request:error`: generic client error in making the call.
* `server:error`: generic server error

businessVersion
===

Business data can be modified from different sources (another device, from the admin, ...). To inform devices of new
data while still limiting sending uselessly the whole business data at each request, the server maintains a "version" of
the business data. Any modification to the business data bumps the version, be it from a device, from the admin, from
another device, ... When the device makes a request, it also sends the last `businessVersion` it received in its JSON
request. The server will check if the business data was updated from another source than the device (it checks if the
`businessVersion` changed). If so, the updated business data (only the part that changed) will be sent alongside the
response's `data` in a `upToDateBusinessData` attribute. If no change occurred, this attribute will not be present. Note
that a modification resulting from the device's request will bump the `businessVersion`, but the modified data will not
be in the response, if it is the only change that happened, since the device already knows about it. That way

API methods
===

`POST /device/register`
---

Registers the device to use the API for this business by sending the passcode generated in the admin. If correctly
authenticated, the response will contain the token to use for the next request.

### Request `data`
* `passcode`: (string) pass code generated in the admin for a new or existing device.

Note that this method doesn't use a `token` (it is not protected by authentication).

### Response `data`
None returned.

### Other errors that can be returned
If the passcode is not valid, will return a `auth:failed` error code.

`POST /register/open`
---

Opens a new Register and assigns it as the currentRegister on the authenticated Device. Note that the device must not
have a current register that is already opened, else an error will be returned and the call will be ignored.

### Request `data`
* `employee`: (string) Name of the employee opening the register
* `cashAmount`: (float, >= 0) Amount of cash in the register at opening

### Response `data`
None returned
