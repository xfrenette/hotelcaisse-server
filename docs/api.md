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
* `businessVersion`: (string) current business data version (see "businessVersion attribute" section below).
* `error`: (object) Present only if `status` is `"error"`. Contains the following keys: `code`: (string) error code; and
    `message`: (string) optional message giving more details for the error. This error message will never contain sensitive
    information, but is probably too technical to show the end user. It is also always in English.
* `business`: (object) If the server determines that the device needs an up to date Business instance (ex: because
    of an outdated `businessVersion` or explicitly requested, see `POST /api/business`), this object will contain the
    updated data. It may be a partial Business object, containing only updated attributes (ex: only `rooms` and
    `transactionModes`) or a full Business instance. See "business attribute" section below.
* `deviceRegister`: (object) If the server determines that the device needs an up to date Register instance (the Register
    of the device), this object will contain the (full) Register. See "deviceRegister attribute" below.
* `token`: (string) if the device is correctly authenticated, contains the token to send in the next request. It can be
    different than the one used in the current request, so always use in your requests the one received in the last
    response. Note that if you make a request and an error is returned, the error will probably not have a `token`. For
    your next request, use the last token you received.
    
General error codes
---

The following error codes can be returned by (almost) all API methods (in the `error.code` JSON attribute):

* `auth:failed`: For all API methods that require a valid token.
* `request:notFound`: when requesting a non-existing API method
* `request:error`: returned anytime the request contains an error (a client error). It can be bad JSON, missing
    parameters, invalid register state, ...
* `server:error`: generic server error

`businessVersion` attribute
---

Business data can be modified from different sources (another device, from the admin, ...). To inform devices of new
data while still limiting sending uselessly the whole business data at each request, the server maintains a "version" of
the business data. Any modification to the business data bumps the version, be it from a device, from the admin, from
another device, ... When the device makes a request, it also sends the last `businessVersion` it received in its JSON
request. The server will check if the business data was updated from another source than the device (it checks if the
`businessVersion` changed). If so, the updated business data (only the part that changed) will be sent alongside the
response's `data` in a `upToDateBusinessData` attribute. If no change occurred, this attribute will not be present. Note
that a modification resulting from the device's request will bump the `businessVersion`, but the modified data will not
be in the response, if it is the only change that happened, since the device already knows about it. That way

`business` attribute
---

Only in the response, contains a full or partial Business instance containing the latest Business data. If partial,
contains only the modified attributes (when compared with the attributes referenced by the `businessVersion` attribute
in the request). The object has the following attributes:

* `rooms` (array) List of currently available rooms
* `rooms.*.id` (number) Id of the room
* `rooms.*.name` (string) Name of the room
* `taxes` (array) List of taxes used in the system
* `taxes.*.id` (number) Id of the tax
* `taxes.*.name` (string) Name of the tax
* `transactionModes` (array) List of currently available transaction modes
* `transactionModes.*.id` (number) Id of the transaction mode
* `transactionModes.*.name` (string) Name of the transaction mode
* `products` (array) Flat list (product variants are at the same level as their parent) of all the currently available
    products.
* `products.*.id` (number) Id of the product
* `products.*.name` (string) Name of the product
* `products.*.description` (string, optional) Description of the product
* `products.*.price` (float) Unit price of the product
* `products.*.taxes` (array, optional) List of tax amounts applied for a single unit
* `products.*.taxes.tax` (number) Id of the tax (see `taxes.*.id)
* `products.*.taxes.amount` (float) Effective (absolute, in money) amount of the tax for a single unit
* `products.*.variants` (array) Array of ids of the variant products (see `products.*.id`)
* `customerFields` (array) Fields for a new Customer
* `customerFields.*.id` (number) Id of the field
* `customerFields.*.type` (string) Type of this field
* `customerFields.*.label` (string) Label of this field
* `customerFields.*.role` (string, optional) Role of this field
* `customerFields.*.required` (boolean, optional, default:false) If this field is a required field
* `customerFields.*.defaultValue` (string, optional) If this field has a default value
* `customerFields.*.values` (array, optional) If this field is a multi choice field (ex: "SelectField"), the different values
* `roomSelectionFields` (array) Fields for a new RoomSelection
* `roomSelectionFields.*.id` (number) Id of the field
* `roomSelectionFields.*.type` (string) Type of this field
* `roomSelectionFields.*.label` (string) Label of this field
* `roomSelectionFields.*.role` (string, optional) Role of this field
* `roomSelectionFields.*.required` (boolean, optional, default:false) If this field is a required field
* `roomSelectionFields.*.defaultValue` (string, optional) If this field has a default value
* `roomSelectionFields.*.values` (array, optional) If this field is a multi choice field (ex: "SelectField"), the different values
* `rootProductCategory` (category, see below) The root ProductCategory. See `category` below for definition.

`category` type (like rootProductCategory):
* `id` (number) Id of the category
* `name` (string) Name of the category
* `products` (array) List of ids of the products of this category (see `products.*.id`)
* `categories` (array, optional) List of `category` (recursion) that are sub-categories of this category.

`deviceRegister` attribute
---

Only in the response, contains a full Register instance (only present if explicitly requested or if the server judges it
applicable, by comparing the `businessVersion` of the request). Contains the following attributes:

* `uuid` (string) UUID that was assigned when the Register was created by a client
* `cashMovements` (array) List of CashMovements of this Register
* `cashMovements.*.uuid` (string) UUID of the CashMovement
* `cashMovements.*.note` (string) Note of the CashMovement
* `cashMovements.*.amount` (float) Amount of the CashMovement

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

Opens a new Register and assigns it as the currentRegister on the authenticated Device. Note that the Device must not
have a current register that is already opened, else an error will be returned and the call will be ignored.

### Request `data`
* `uuid`: (string) Client generated UUID for the new Register (must be unique)
* `employee`: (string) Name of the employee opening the register
* `cashAmount`: (float, >= 0) Amount of cash in the register at opening

### Response `data`
None returned

`POST /register/close`
---

Closes the Register currently assigned to the authenticated Device. Note that the Device must have an opened Register
assigned with the same UUID, else an error will be returned and the call will be ignored.

### Request `data`
* `uuid`: (string) UUID of the Register. Must be supplied to be sure the client is closing the expected Register and we
    don't end up with unexpected results.
* `cashAmount`: (float, >= 0) Amount of cash in the register at opening
* `POSTRef`: (string) Reference number of the Point Of Sale Terminal (POST) batch
* `POSTAmount`: (float, >= 0) Amount of the POST batch

### Response `data`
None returned

`POST /cashMovements/add`
---

Creates a new CashMovement and assigns it to the current Register of the Device. The Register must be opened, else an
error will be returned and the call will be ignored.

### Request `data`
* `uuid`: (string) UUID generated by the client for this CashMovement
* `amount`: (float, != 0) Non-zero (can be negative) amount of the CashMovement
* `note`: (string) Description of the CashMovement

### Response `data`
None returned

`POST /cashMovements/delete`
---

Deletes the CashMovement with the supplied uuid. Only CashMovements in the Device's current Register can be deleted, and
only if the Register is opened.

### Request `data`
* `uuid`: (string) UUID of the CashMovement to delete

### Response `data`
None returned

`POST /orders/new`
---

Creates a new Order from the data. The register must be opened since the data can contain transactions. If it is not the
case or if any validation error occurs, an error is returned and the request is ignored.

### Request `data`
* `uuid`: (string) UUID of the new Order to create, generated by the client
* `note`: (string, optional) Note for the Order 
* `customer`: (array) Data for Customer (see next lines)
* `customer.fieldValues`: (array) Field values (see next lines)
* `customer.fieldValues.*.field`: (number) Id of the Field
* `customer.fieldValues.*.value`: (string) Value for the Field
* `credits`: (array, optional) Credits of the Order (see next lines)
* `credits.*.uuid`: (string) Generated UUID for this credit
* `credits.*.note`: (string) Note for this credit
* `credits.*.amount`: (float, > 0) Amount for this credit
* `transactions`: (array, optional) Transactions of the Order (see next lines)
* `transactions.*.uuid`: (string) Generated UUID for this transaction
* `transactions.*.amount`: (float, != 0) Amount of this transaction (positive means payment, negative means refund)
* `transactions.*.transactionMode`: (numeric) Id of the TransactionMode used
* `items`: (array, optional) Items of this Order (see next lines)
* `items.*.uuid`: (string) Generated UUID for this Item
* `items.*.quantity`: (float, != 0) Quantity of the product for this Item (negative is for refunded item)
* `items.*.product`: (array) Information about the product (see next lines)
* `items.*.product.name`: (string) Name of the product (must be a full name if a variant, including the parent's name)
* `items.*.product.price`: (float, >= 0) Unit price (before taxes) this product was sold (positive, even if it is a refunded item)
* `items.*.product.product_id`: (numeric, optional) Unless this is a custom product, id of the original Business' Product
* `items.*.product.taxes`: (array, optional) Taxes applied for a unit of the product
* `items.*.product.taxes.*.tax_id`: (numeric) Id of the Tax
* `items.*.product.taxes.*.amount`: (float, > 0) Applied amount (absolute, no percentage) for a single unit
* `roomSelections`: (array, optional) List of RoomSelections (see next lines)
* `roomSelections.*.uuid`: (string) Generated UUID for this RoomSelections
* `roomSelections.*.startDate`: (numeric) Timestamp (seconds) for the start date
* `roomSelections.*.endDate`: (numeric) Timestamp (seconds) for the end date (must be at least 24 hours after startDate)
* `roomSelections.*.room`: (numeric) Id of the Room
* `roomSelections.*.fieldValues`: (array) Field values for the RoomSelection (see below)
* `roomSelections.*.fieldValues.*.field`: (numeric) Id of the Field
* `roomSelections.*.fieldValues.*.value`: (string) Value for the Field

### Response `data`
None returned

`POST /orders/edit`
---

Updates an existing Order from the data. The register must be opened if the data contains transactions. If it is
not the case or if any validation error occurs, an error is returned and the request is ignored. First level attributes
are all optional (except uuid), but their children are required if the parent is present.

Some attributes are "editable lists", others are "add-only lists".
* "editable list": existing items can be edited (just include the new data) and deleted (do not include the item in the
    list), and new items can be added. This requires that non-edited items be also in the list.
* "add-only list": existing items cannot be modified or deleted (ex: for accounting reasons). All items in the list
    will be considered a new item and will be created.

### Request `data`
* `uuid`: (string) UUID of the Order to edit
* `note`: (string, optional) Note for the Order 
* `customer`: (array, optional) Data for Customer (see next lines)
* `customer.fieldValues`: ("editable list", array) Field values (see next lines)
* `customer.fieldValues.*.field`: (number) Id of the Field
* `customer.fieldValues.*.value`: (string) Value for the Field
* `credits`: ("editable list", array, optional) Credits of the Order (see next lines)
* `credits.*.uuid`: (string) UUID of this credit (a new one if creating, an existing one if editing)
* `credits.*.note`: (string) Note for this credit
* `credits.*.amount`: (float, > 0) Amount for this credit
* `transactions`: ("add-only list", array, optional) Transactions of the Order (see next lines)
* `transactions.*.uuid`: (string) Generated UUID for this transaction
* `transactions.*.amount`: (float, != 0) Amount of this transaction (positive means payment, negative means refund)
* `transactions.*.transactionMode`: (numeric) Id of the TransactionMode used
* `items`: ("add-only list", array, optional) Items of this Order (see next lines)
* `items.*.uuid`: (string) Generated UUID for this Item
* `items.*.quantity`: (float, != 0) Quantity of the product for this Item (negative is for refunded item)
* `items.*.product`: (array) Information about the product (see next lines)
* `items.*.product.name`: (string) Name of the product (must be a full name if a variant, including the parent's name)
* `items.*.product.price`: (float, >= 0) Unit price (before taxes) this product was sold (positive, even if it is a refunded item)
* `items.*.product.product_id`: (numeric, optional) Unless this is a custom product, id of the original Business' Product
* `items.*.product.taxes`: (array, optional) Taxes applied for a unit of the product
* `items.*.product.taxes.*.tax_id`: (numeric) Id of the Tax
* `items.*.product.taxes.*.amount`: (float, > 0) Applied amount (absolute, no percentage) for a single unit
* `roomSelections`: ("editable list", array, optional) List of RoomSelections (see next lines)
* `roomSelections.*.uuid`: (string) UUID for this RoomSelections (a new one if creating, an existing one if editing)
* `roomSelections.*.startDate`: (numeric) Timestamp (seconds) for the start date
* `roomSelections.*.endDate`: (numeric) Timestamp (seconds) for the end date (must be at least 24 hours after startDate)
* `roomSelections.*.room`: (numeric) Id of the Room
* `roomSelections.*.fieldValues`: (array) Field values for the RoomSelection (see below)
* `roomSelections.*.fieldValues.*.field`: (numeric) Id of the Field
* `roomSelections.*.fieldValues.*.value`: (string) Value for the Field

### Response `data`
None returned
