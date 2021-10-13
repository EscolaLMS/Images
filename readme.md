# Image processing module

All images are optimized with [image-optimizer](https://packagist.org/packages/spatie/image-optimizer).

For best results binaries must [be installed](https://github.com/spatie/image-optimizer#optimization-tools).

## Installation

`composer require escolalms/images`


## Examples Resizing

### one image as redirect result 

Basic resize is made by URL API call which redirects to new created file 

Example 

- `http://localhost/api/images/img?path=test.jpg&w=100` call should return resized image to width 100px
- checks if file exsitis 
- if not, creates one with availabe libraries 
- returns 302 redirect 
- example `http://localhost/storage/imgcache/891ee133a8bb111497d494d4c91fe292d9d16bb3.jpg` (assuming you're using local disk storage, in case of s3 location origin would differ)

### Json resizing many images at once 

Example POST call like 

```bash
POST /api/images/img HTTP/1.1
Host: localhost:1000
Content-Type: application/json
Content-Length: 212

{
	"paths": [{
		"path": "tutor_avatar.jpg",
		"params": {
			"w": 100
		}
	}, {
		"path": "tutor_avatar.jpg",
		"params": {
			"w": 200
		}
	}, {
		"path": "tutor_avatar.jpg",
		"params": {
			"w": 300
		}
	}]
} 
```

generates following result

```json
[
    {
        "url": "http://localhost/storage/imgcache/3421584c40d270d0fa7ef0c31445a1565db07cb4.jpg",
        "path": "imgcache/3421584c40d270d0fa7ef0c31445a1565db07cb4.jpg",
        "hash": "3421584c40d270d0fa7ef0c31445a1565db07cb4"
    },
    {
        "url": "http://localhost/storage/imgcache/7efc528c2cc7b57d79a42f80d2c1891b517cabfe.jpg",
        "path": "imgcache/7efc528c2cc7b57d79a42f80d2c1891b517cabfe.jpg",
        "hash": "7efc528c2cc7b57d79a42f80d2c1891b517cabfe"
    },
    {
        "url": "http://localhost/storage/imgcache/5db4f572d8c8b1cb6ad97a3bffc9fd6c18b56cc3.jpg",
        "path": "imgcache/5db4f572d8c8b1cb6ad97a3bffc9fd6c18b56cc3.jpg",
        "hash": "5db4f572d8c8b1cb6ad97a3bffc9fd6c18b56cc3"
    }
] 
```

## Hashing algorithm 

There is simple algorithm to guess the result image URL 

```php 
$path = 'test.jpg';
$params = ['w'=>100];

$hash = sha1($path.json_encode($params));
```

then result URL would be  

```php
$output_file = $url_prefix.$hash.$extension;
```

## Frontend implmementation 

Below is our totally headless approach on generating images 

The following example tries to achives 2 purposes 
- generate image on fly, frontend decide what sizes are needed
- images are not served by API 

The idea is that since we know tha hashing algoritm for cached images we can guess that the URL will be like. 
If that URL is throwing 404 then we're calling the API endpoint to generate one. 
Fortunately this endpoint creates an requested image, caches it and returns redirect which is good for image src. 

```html
<script>
	  // Initial variables 
      const imgPath = "tutor_avatar.jpg";
      const imgPrefix = "http://localhost/storage/imgcache";
      const apiUrl = "http://localhost/api/images/img";
      const rndWith = Math.round(Math.random() * 1000);
	  const params = { w: rndWith.toString() }; // random with params
	  // super important that all param values are strings 
	  // hash from { w: 100 } is different then { w: "100" }
       
	  // stright forward helper to convert obejct to URL query params 
	  const paramsToUrl = (params) =>
        Object.entries(params)
          .map((e) => e.join("="))
          .join("&");

	/** 
	 * @param string path, example "tutor_avatar.jpg"
	 * @param array params, example { w: "100" } or { w: "100", h: "10" }
	 * @return Image 
	 */ 
      const getImage = (path, params) => {
        const hash = SHA1(path + JSON.stringify(params));
        const url = `${imgPrefix}/${hash}.${path.split(".").pop()}`;
        const imgApiUrl = `${apiUrl}/?path=${imgPath}&${paramsToUrl(params)}`;
        const image = new Image();
        image.src = url;
        image.onerror = () => {
          if (image.src != imgApiUrl) {
            // the cached version does not exists yet, lets call API to create one and redirect.
            image.src = imgApiUrl;
          }
        };

        return image;
      };

      document.body.appendChild(getImage(imgPath, params));
    </script> 
```

Working example is availabe in [docs](docs) folder. 