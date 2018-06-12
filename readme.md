To publish online
===
- Pushes any changes on the Git
- SSH to the server `ssh dev@venteshirdl.com`
- From the home directory, run `./deploy.sh`

Sample API request
===
POST http://hirdlpos.xfdev/api/1.0/hirdl/deviceData

{
	"token": "eNrAw6JYXlpGeNiLNejbmz7mIVnDwfSq",
	"dataVersion": "10"
}

To manage (add/edit) products
===
Go to /manage-products. Note that this section doesn't do too much validation and there is no revert (if you delete a product, it cannot be brought back -- note that this doesn't delete the sales of this product, it will still show up in sale statistics, it is just not available for new sales) so it is better that only the developer has access to it.
