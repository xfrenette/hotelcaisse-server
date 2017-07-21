* Some models have a 'uuid' attribute (in addition to their id), others don't. The ones
  with uuid are objects that can be created by the client. It is the id assigned by the
  client when it was created. It is as unique as their id.
* When generating the JSON, all JSON objects will have a 'uuid' attribute. For the models
  that have a 'uuid', this value will be used, for the others, the 'id' will be used.
* Note that the js-app always uses the 'uuid' name
