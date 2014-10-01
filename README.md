apirunner
=========

A command line tool for running and measuring multiple api calls

### Install
```composer update```

### Run
``` sh apirunner```

>...and thats it!


### configure
  
  _configs/endpoints.yml_
 
  If your Api's need Authentication _APIRUNNER_ will go grab your tokens upfront.
```YAML
authentication:
  app_key: KEY
  app_secret: SECRET
  uri:
    base: 'OAuth2.domain/com'
    authorize: '/oauth/dialog'
    token: '/oauth2/token.json'
    verify: '/oauth2/verify-token.json'
    validate_ssl: false
  user_credentials:
    username: paulroon@gmail.com
    password: my_splendid_password
```

For more configs, look in _configs/endpoints.yml_. Its pretty self explanatory (I think) 
