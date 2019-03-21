# Sample Laravel Envoy Deployment Script

The [Envoy.blade.php](https://github.com/lexxyungcarter/laravel-envoy/Envoy.blade.php) file contains a sample config file for automated deployments to production servers.

The script also works fine with the **CD/CI** practices of modern web development and deployment.

![Screenshot](screenshot.jpg?raw=true "Screenshot")

## How to go about it

Laravel envoy is pretty straight-forward. You can read more details [over here](https://laravel.com/docs/5.8/envoy). 

> NB:- Envoy is not tied to Laravel; in fact it is not tied to any programming language. Therefore, one can simply import the Envoy file to the root of their application and start enjoying the **Zero-Downtime** deployment process.

### Assumptions
It is assumed your server uses at least these technologies:
- NGINX
- Ubuntu Server

### Testing Envoy
Run this command in your terminal (locally) to ensure your configuration works.

```bash
envoy run list
```
If task runs successfully, you are good to **Deploy Your App**

### 'Deploying' phase
You need to install **Envoy** in your system. Also, servers running **Ubuntu** and **nginx** are assumed to be *de-facto* standard.

```bash
envoy run deploy --password=secret --npm=true
```
Providing the password enables the script to modify folder owners and permissions. However, one can simply omit specifying the password.

Also, providing `--npm=true` will run `npm install` in the local directories if specified.

```bash
envoy run deploy
```

The script will:
- Clone the git repo to a new folder in the releases directory
- Run `composer install` inside the new repository (release dir)
- Link the `storage` and `.env` to the releases directory (symbolic links)
- Ascertain the **Live** app directory is indeed available. If not, it creates one
- Link the **Live** app directory to the release folder created earlier
- Ascertain permissions are correct (happens only if the password is passed as an option in the command)
- It then lists the folder contents for both the `release` and the `live app` directories.
- Once done, the script switches to your local development folder. Inside each folder (as provided in `$localProjectFolder`), it runs `composer install` and also `npm install` (if `--npm=true` is passed in the command)

Finally, after all tasks have completed running, a notifications is sent to **Slack**. You must create a **Slack App** and generate a **webhook url** before sending out a notification.

## Credits
- [Lexx YungCarter](mailto:lexxyungcarter@gmail.com)
- [Taylor Otwell - Laravel](https://laravel.com) - *for creating such an expressive, beautiful framework*
