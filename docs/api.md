Request format
===

All request are POST with a Content-Type of `application/json` and a JSON body having the following attributes:

* `token`: (string) required for all authenticated requests (that is all requests, except `device/register`)
* `data`: (mixed) put here the data required by the API method you call. Most probably an object. Can be omitted for
    methods that do not require additional input data.
* `dataVersion`: (string) the last `dataVersion` that you received. See "dataVersion" section below. Always
    put the `dataVersion` that you received in the response of your last request. If it is not present, you will not
    receive updated data.

Also, all API request are relative to a "team". This is determine by the URL. Ex:
`https://{my-team-slug}.example.com/api`.

Response format
===

All response will be JSON object with the following attributes :

* `status`: (string) either `"ok"` or `"error"`
* `data`: (mixed) data returned by this API method. Can be not present if the method doesn't return data or in case of error.
* `dataVersion`: (string) current data version (see "dataVersion attribute" section below).
* `error`: (object) Present only if `status` is `"error"`. Contains the following keys: `code`: (string) error code; and
    `message`: (string) optional message giving more details for the error. This error message will never contain sensitive
    information, but is probably too technical to show the end user. It is also always in English.
* `business`: (object) If the server determines that the device needs an up to date Business instance (ex: because
    of an outdated `dataVersion` or explicitly requested, see `POST /api/business`), this object will contain the
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

`dataVersion` attribute
---

Business data can be modified from different sources (another device, from the admin, ...). To inform devices of new
data while still limiting sending uselessly the whole data at each request, the server maintains a "version" of the
data. Any modification to the business data (including the device's register) bumps the version, be it from a device,
from the admin, from another device, ... When the device makes a request, it also sends the last `dataVersion` it
received in its JSON request. The server will check if the data was updated since the last sent version number. If so,
the updated data will be sent alongside the response's `data`. If it is a Business data change (can be partial), it will
in `business` attribute. If it is the device's register, it will be in `deviceRegister`. If no change occurred, those
attributes will not be present. Note that a modification resulting from the device's request will bump the
`dataVersion`, but the modified data will not be in the response, if it is the only change that happened, since the
device already knows about it. That way

`business` attribute
---

Only in the response, contains a full or partial Business instance containing the latest Business data. If partial,
contains only the modified attributes (when compared with the attributes referenced by the `dataVersion` attribute
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
* `transactionModes.*.type` (string) Type of the transaction mode
* `products` (array) Flat list (product variants are at the same level as their parent) of all the currently available
    products.
* `products.*.id` (number) Id of the product
* `products.*.name` (string) Name of the product
* `products.*.description` (string, optional) Description of the product
* `products.*.price` (float) Unit price of the product
* `products.*.taxes` (array, optional) List of tax amounts applied for a single unit
* `products.*.taxes.*.id` (number) Id of the tax (see `taxes.*.id)
* `products.*.taxes.*.amount` (float) Effective (absolute, in money) amount of the tax for a single unit
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

Only in the response, contains the full data of the device's Register instance (only present if explicitly requested or
if the server judges it applicable, by comparing the `dataVersion` of the request). Contains the following attributes:

* `uuid` (string) UUID that was assigned when the Register was created by a client
* `state` (0|1) Current state of the register (0 = closed, 1 = opened)
* `employee` (string) Employee that opened the register
* `openingCash` (float) Amount of cash at register opening
* `openedAt` (integer) Timestamp when the Register was opened
* `cashMovements` (array) List of CashMovements of this Register
* `cashMovements.*.uuid` (string) UUID of the CashMovement
* `cashMovements.*.note` (string) Note of the CashMovement
* `cashMovements.*.amount` (float) Amount of the CashMovement
* `cashMovements.*.createdAt` (integer) Timestamp of the creation date

API methods
===

`POST /device/link`
---

Links a physical device to a Device already defined for the Team. This device will then be allowed access to the API.
The Team owner can obtain the passcode from the admin. If correctly authenticated, the response will contain the token
to use for the next request.

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
* `customer.fieldValues.*.fieldId`: (number) Id of the Field
* `customer.fieldValues.*.value`: (string) Value for the Field
* `credits`: (array, optional) Credits of the Order (see next lines)
* `credits.*.uuid`: (string) Generated UUID for this credit
* `credits.*.note`: (string) Note for this credit
* `credits.*.amount`: (float, > 0) Amount for this credit
* `transactions`: (array, optional) Transactions of the Order (see next lines)
* `transactions.*.uuid`: (string) Generated UUID for this transaction
* `transactions.*.amount`: (float, != 0) Amount of this transaction (positive means payment, negative means refund)
* `transactions.*.transactionModeId`: (numeric) Id of the TransactionMode used
* `items`: (array, optional) Items of this Order (see next lines)
* `items.*.uuid`: (string) Generated UUID for this Item
* `items.*.quantity`: (float, != 0) Quantity of the product for this Item (negative is for refunded item)
* `items.*.product`: (array) Information about the product (see next lines)
* `items.*.product.name`: (string) Name of the product (must be a full name if a variant, including the parent's name)
* `items.*.product.price`: (float, >= 0) Unit price (before taxes) this product was sold (positive, even if it is a refunded item)
* `items.*.product.productId`: (numeric, optional) Unless this is a custom product, id of the original Business' Product
* `items.*.product.taxes`: (array, optional) Taxes applied for a unit of the product
* `items.*.product.taxes.*.id`: (numeric) Id of the Tax
* `items.*.product.taxes.*.amount`: (float, > 0) Applied amount (absolute, no percentage) for a single unit
* `roomSelections`: (array, optional) List of RoomSelections (see next lines)
* `roomSelections.*.uuid`: (string) Generated UUID for this RoomSelections
* `roomSelections.*.startDate`: (numeric) Timestamp (seconds) for the start date
* `roomSelections.*.endDate`: (numeric) Timestamp (seconds) for the end date (must be at least 24 hours after startDate)
* `roomSelections.*.room`: (numeric) Id of the Room
* `roomSelections.*.fieldValues`: (array) Field values for the RoomSelection (see below)
* `roomSelections.*.fieldValues.*.fieldId`: (numeric) Id of the Field
* `roomSelections.*.fieldValues.*.value`: (string) Value for the Field

**Notes**
* `transactions.*.transactionModeId`: the client does not have to worry if this id references a still
    existing TransactionMode (at the time the request is sent), because, on the server, the TransactionModes are
    immutable: when a TransactionMode is modified, in fact a new one is created with a new id and the old one is kept
    archived.
* `roomSelections.*.room`: same note as `transactions.*.transactionModeId`.

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
* `customer.fieldValues.*.fieldId`: (number) Id of the Field
* `customer.fieldValues.*.value`: (string) Value for the Field
* `credits`: ("editable list", array, optional) Credits of the Order (see next lines)
* `credits.*.uuid`: (string) UUID of this credit (a new one if creating, an existing one if editing)
* `credits.*.note`: (string) Note for this credit
* `credits.*.amount`: (float, > 0) Amount for this credit
* `transactions`: ("add-only list", array, optional) Transactions of the Order (see next lines)
* `transactions.*.uuid`: (string) Generated UUID for this transaction
* `transactions.*.amount`: (float, != 0) Amount of this transaction (positive means payment, negative means refund)
* `transactions.*.transactionModeId`: (numeric) Id of the TransactionMode used
* `items`: ("add-only list", array, optional) Items of this Order (see next lines)
* `items.*.uuid`: (string) Generated UUID for this Item
* `items.*.quantity`: (float, != 0) Quantity of the product for this Item (negative is for refunded item)
* `items.*.product`: (array) Information about the product (see next lines)
* `items.*.product.name`: (string) Name of the product (must be a full name if a variant, including the parent's name)
* `items.*.product.price`: (float, >= 0) Unit price (before taxes) this product was sold (positive, even if it is a refunded item)
* `items.*.product.productId`: (numeric, optional) Unless this is a custom product, id of the original Business' Product
* `items.*.product.taxes`: (array, optional) Taxes applied for a unit of the product
* `items.*.product.taxes.*.id`: (numeric) Id of the Tax
* `items.*.product.taxes.*.amount`: (float, > 0) Applied amount (absolute, no percentage) for a single unit
* `roomSelections`: ("editable list", array, optional) List of RoomSelections (see next lines)
* `roomSelections.*.uuid`: (string) UUID for this RoomSelections (a new one if creating, an existing one if editing)
* `roomSelections.*.startDate`: (numeric) Timestamp (seconds) for the start date
* `roomSelections.*.endDate`: (numeric) Timestamp (seconds) for the end date (must be at least 24 hours after startDate)
* `roomSelections.*.room`: (numeric) Id of the Room
* `roomSelections.*.fieldValues`: (array) Field values for the RoomSelection (see below)
* `roomSelections.*.fieldValues.*.fieldId`: (numeric) Id of the Field
* `roomSelections.*.fieldValues.*.value`: (string) Value for the Field

**Notes**
* `transactions.*.transactionModeId`: see note in `POST /orders/create`.
* `roomSelections.*.room`: see note in `POST /orders/create`.

### Response `data`
None returned

`POST /orders`
---

Returns the list of Orders for the current Business. The Orders are returned in reverse order, sorted by creation date,
so the last Orders are returned first. A `quantity` must be provided. If a `from` (an Order UUID) is supplied, the list
are the Orders following (by creation date) the `from`.

### Request `data`
* `quantity`: (number) Number of Order to return (it has a maximum defined by the server).
* `from`: (optional, string) UUID of the Order after which we want to get the Orders. If not defined, will return the
    last Orders.

### Response `data`
Returns an array of Orders, where is is an object with the following attributes:

* `*.uuid` (string) UUID of the Order
* `*.note` (string) Note of the Order
* `*.customer` (object) Customer info of the Order
* `*.customer.fieldValues` (array) Field values
* `*.customer.fieldValues.*.fieldId` (number) Id of the Field
* `*.customer.fieldValues.*.value` (string) Value of the Field
* `*.items` (array) Items of the Order
* `*.items.*.uuid` (string) UUID of the Item
* `*.items.*.quantity` (float) Quantity of the Item (if negative, the item is a refunded item)
* `*.items.*.createdAt` (int) Timestamp of the creation date
* `*.items.*.product` (object) Product info
* `*.items.*.product.name` (string) Full name of the Item
* `*.items.*.product.price` (float) Unit price of the Product
* `*.items.*.product.taxes` (array) List of applied taxes for a unit.
* `*.items.*.product.taxes.*.id` (number) Id of the Tax object that was used to calculate the amount. The Tax may no
        longer exist when the Order is viewed.
* `*.items.*.product.taxes.*.amount` (float) Unit amount of tax for this product. This is the money amount applied, not
        percentage, if it was a percentage.
* `*.transactions` (array) List of Transactions
* `*.transactions.*.uuid` (string) UUID of the Transaction
* `*.transactions.*.amount` (float) Amount of the Transaction (if negative, it is a refund)
* `*.transactions.*.transactionMode` (object) TransactionMode of this transactions
* `*.transactions.*.transactionMode.id` (number) Id of the TransactionMode
* `*.transactions.*.transactionMode.name` (string) Name of the TransactionMode
* `*.transactions.*.transactionMode.type` (string|null) Type of the TransactionMode
* `*.credits` (array) List of Credits
* `*.credits.*.uuid` (string) UUID of the Credit
* `*.credits.*.note` (string) Note of the Credit
* `*.credits.*.amount` (float) Amount of the Credit
* `*.roomSelections` (array) List of RoomSelections
* `*.roomSelections.*.uuid` (string) UUID of the RoomSelections
* `*.roomSelections.*.startDate` (number) Timestamp of the start date
* `*.roomSelections.*.endDate` (number) Timestamp of the end date
* `*.roomSelections.*.room` (object) Room instance
* `*.roomSelections.*.room.id` (number) Id of the Room
* `*.roomSelections.*.room.name` (string) Name of the Room
* `*.roomSelections.fieldValues` (array) Field values
* `*.roomSelections.fieldValues.*.fieldId` (number) Id of the Field
* `*.roomSelections.fieldValues.*.value` (string) Value of the Field

**Notes**
* `*.transactions.*.transactionMode`: Since the TransactionMode used when the Order was created may not exist
    anymore, the whole instance is included for each transaction, instead of simply an id.
* `*.roomSelections.*.room`: Same comment as `transactions.*.transactionMode`.
