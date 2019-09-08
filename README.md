# Installation

Module drupal 8
Modify the composer.json to look reflect this

```json
,
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
                {
            "type": "package",
            "package": {
                "name": "custom/napay",
                "version": "dev-master",
                "type":"drupal-custom-module",
                "source": {
                    "url": "https://github.com/bossygit/napay.git",
                    "type": "git",
                    "reference": "master"
                }
            }
        }

    ],
```


