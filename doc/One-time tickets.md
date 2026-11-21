# One-time tickets

A one-time ticket is a link that allows whoever receives it to create **exactly
one** document on your PrivateBin instance, without having access to the
instance's create page otherwise.

This is useful if the creation of documents is restricted, i.e. behind
authentication in a reverse proxy, but you want to receive text or files from
someone who has no access: you create a ticket, send them the link, they use it
to create a document and send you the link of that document.

The feature is optional and disabled by default.

## How it works

1. An authenticated user opens the create page and presses the **One-time
   ticket** button. The button is only enabled while the editor and the file
   list are empty. The expiration time selected at that moment becomes the
   lifetime of the ticket. Tickets can't be created with the expiration
   "never".
2. PrivateBin shows a ticket link, `https://privatebin.example/?<ticket id>#<key>`,
   which is sent to the recipient by any means, i.e. using the e-mail button.
3. The recipient opens the link. They see the usual create page, with
   burn-after-reading pre-selected. They can change the options, set a
   password, choose the expiration and attach files, as usual. The discussion
   option is not offered, documents created with a ticket can't allow
   discussions.
4. When they press send, the browser submits the document to
   `POST /?ticket=<ticket id>`. The server checks the ticket, deletes it and
   stores the new document. A ticket can't be used a second time.
5. The recipient sends the link of the new document back, i.e. using the
   e-mail button or any other channel. PrivateBin doesn't send e-mails itself.

```
 Originator (authenticated)              Recipient (no access)
 --------------------------              ---------------------
 POST /            (ticket request)
 <- ticket id
 link: /?<ticket id>#<key>  ------------>  GET /?<ticket id>   (create page)
                                           POST /?ticket=<ticket id>
                                           <- new document id
 <------------ /?<document id>#<key2> ---  link of the new document
```

### Properties

* The ticket carries no secret. Its content is a fixed placeholder, encrypted
  like any other document, and the zero-knowledge model is unchanged: the
  server never sees the keys or the content of the document created.
* A ticket is stored like an ordinary document, using any of the storage
  backends (Filesystem, Database, GoogleCloudStorage, S3Storage). It is marked
  in its unencrypted metadata as `ticket: 1`, which the server uses to
  recognise it.
* The server only accepts a ticket marker if the feature is enabled, an
  expiration is set, and burn-after-reading and discussions are off. Documents
  created using a ticket can't carry the marker themselves, so tickets can't be
  chained, and comments can't be posted using a ticket.
* Unknown, expired and already used tickets all result in the same generic
  error. An expired ticket is removed when it is tried.
* The ticket is only consumed once the document sent with it was validated, so
  a malformed or refused request doesn't use it up.
* The ticket ID is the only credential needed to use a ticket, the key after
  the `#` isn't. Treat the ticket link like a password until it is used: the ID
  appears in the logs of the proxy and in the browser history, where anyone who
  can read them could use an unused ticket. PrivateBin sends
  `Referrer-Policy: no-referrer`, so it isn't passed on to other sites.
* Tickets are created with `POST /`, i.e. the normal create request with the
  ticket marker. Whatever restricts who can create documents therefore also
  restricts who can create tickets. Only the ticket request itself,
  `POST /?ticket=<ticket id>`, is meant to be reachable without authentication.
* "One time" holds for requests one after the other. The ticket is read and then
  deleted, and the storage backends offer no atomic way to do both at once, so
  in theory, two requests that arrive at the very same moment could both use the
  same ticket. In a test with 16 simultaneous requests per ticket, using the
  Filesystem backend, this didn't happen. If it did, the result would be one
  additional document, still subject to the size limit and traffic limiter.
* The server refuses documents that allow discussions if they are created with
  a ticket, so this doesn't rely on the browser hiding the option.
* The recipient chooses the options of the new document within the limits of
  the configuration, including its expiration, so they can create a document
  that lives as long as your configuration allows.
* The usual limits apply to the ticket request: the traffic limiter, the size
  limit, and the other restrictions of the configuration.

## Configuration

In the `[main]` section of `cfg/conf.php`:

```ini
; enable or disable one-time tickets, defaults to false
onetimeticket = true
```

Nothing else is needed if PrivateBin is accessible without restrictions. The
feature only matters if something in front of PrivateBin restricts who may
create documents.

### Complete configuration for a reverse proxy (copy and paste)

If PrivateBin is secured by an NGINX reverse proxy, this is everything needed
in one place. It lets the recipient of a ticket create one document without
authenticating, keeps everything else behind authentication, passes the
visitor's IP address on, limits the size and rate of ticket requests, and
doesn't trust headers sent by the client. The sections further down explain
what each part does.

**`cfg/conf.php` of PrivateBin**

```ini
[main]
onetimeticket = true

[traffic]
; the IP address of the visitor, set by the proxy below
header = "X_FORWARDED_FOR"
```

**NGINX**, the `map`, `limit_req_zone` and `upstream` directives go into the
`http` context, i.e. a file in `conf.d`, the rest into your `server` block:

```nginx
upstream privatebin_backend {
    server 127.0.0.1:8080;           # adapt to where PrivateBin is served
}

# requests that may pass without authentication
map "$request_method:$request_uri" $public_request {
    default                                         0;
    "~^GET:/\?."                                    1;  # view and delete documents
    "~^(GET|HEAD):/(js|css|img|i18n|vendor|fonts)/" 1;  # static assets
    "~^GET:/(favicon\.ico|manifest\.json|robots\.txt)" 1;
    "~^POST:/\?ticket=[0-9a-f]{16}$"                1;  # one-time ticket request
}

# the single request that uses a ticket, handled by its own location below
map "$request_method:$request_uri" $ticket_request {
    default                                         0;
    "~^POST:/\?ticket=[0-9a-f]{16}$"                1;
}

# basic auth realm, or "off" for public requests
map $public_request $auth_basic_required {
    1                                               off;
    default                                         "PrivateBin";
}

# at most 6 ticket requests per minute and visitor, with a short burst
limit_req_zone $binary_remote_addr zone=privatebin_ticket:10m rate=6r/m;

server {
    listen 443 ssl;
    server_name privatebin.example;

    # certificates, and for mTLS: ssl_client_certificate and
    # ssl_verify_client, as you use them today

    # everything that isn't a ticket request
    location / {
        # ticket requests are handed to the location below, query string kept
        if ($ticket_request) {
            rewrite ^ /_ticket last;
        }

        # authentication: replace with your own, i.e. auth_request or client
        # certificates, and let requests with $public_request = 1 skip it
        auth_basic           $auth_basic_required;
        auth_basic_user_file /etc/nginx/privatebin.htpasswd;

        # upload limit for authenticated users, adapt to your sizelimit
        client_max_body_size 15m;

        # replace any value sent by the client, never append to it
        proxy_set_header Host              $host;
        proxy_set_header X-Forwarded-For   $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://privatebin_backend;
    }

    # unauthenticated ticket requests, only reachable by the rewrite above
    location = /_ticket {
        internal;

        limit_req        zone=privatebin_ticket burst=5 nodelay;
        limit_req_status 429;

        # the `sizelimit` of PrivateBin (default 10 MiB) plus ~40% encoding
        client_max_body_size 15m;

        proxy_set_header Host              $host;
        proxy_set_header X-Forwarded-For   $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://privatebin_backend/;
    }
}
```

The `upstream`, `map` and `limit_req_zone` directives are only valid in the
`http` context. If your NGINX setup includes per-site snippet files inside the
`server` block, put them in a file in `conf.d/` (or `nginx.conf`) instead, and
keep only the `location` blocks in the per-site file.

Things to adapt: the upstream address, the server name and certificates, the
authentication method, and the size limits, which need to stay in line with
`sizelimit` in `cfg/conf.php`. If NGINX is itself behind another proxy or load
balancer, add the `realip` configuration described below. Reload NGINX, create
a ticket, and test it from a private window: the ticket request should show in
the access log as served by `/_ticket`. A plain `POST /` without authentication
must still be refused.

## Using a reverse proxy

If the instance is secured by a reverse proxy, the recipient needs to get
through it twice:

| Request                         | Purpose                         | Typically allowed already? |
|---------------------------------|---------------------------------|----------------------------|
| `GET /?<ticket id>#<key>`       | load the create page and ticket | yes, if documents can be viewed without authentication |
| `POST /?ticket=<ticket id>`     | create the single document      | **no, this needs a new rule** |

Everything else, in particular `POST /` (the creation of an ordinary document
and of tickets), stays restricted.

The proxy can't tell if a ticket is valid, this is up to PrivateBin. Anyone can
send a request with a well-formed ticket ID, but PrivateBin rejects it unless
the ticket exists, so no document is created. Such requests still reach
PrivateBin, where the traffic limiter applies.

A ticket ID consists of 16 hexadecimal characters. Match it exactly and anchor
the pattern, so that nothing else can be appended to the query string.

### How the NGINX rules work

The complete configuration is above. The following explains its parts. It assumes that PrivateBin sits behind NGINX, which decides per
request if authentication is required. The `map` key is the request method and
the request URI. Adapt names and paths to your setup.

```nginx
# requests that may pass without authentication
map "$request_method:$request_uri" $public_request {
    default                              0;

    # viewing and deleting documents: GET /?<document id>...
    "~^GET:/\?."                         1;

    # static assets
    "~^(GET|HEAD):/(js|css|img|i18n|vendor|fonts)/" 1;
    "~^GET:/(favicon\.ico|manifest\.json|robots\.txt)" 1;

    # one-time tickets: the single create request of the recipient
    "~^POST:/\?ticket=[0-9a-f]{16}$"     1;
}

# basic auth realm, or "off" for public requests
map $public_request $auth_basic_required {
    1        off;
    default  "PrivateBin";
}

server {
    listen 443 ssl;
    server_name privatebin.example;

    location / {
        auth_basic           $auth_basic_required;
        auth_basic_user_file /etc/nginx/privatebin.htpasswd;

        proxy_pass http://privatebin-backend;
    }
}
```

The only change that the ticket feature needs is the second entry in the
`$public_request` map. If you already have a rule that lets requests for
viewing documents through, leave it as it is and add the ticket line next to it.

The same map works with other ways to authenticate, i.e. `auth_request` for an
authentication proxy such as oauth2-proxy, or client certificates verified with
`ssl_verify_client optional`: evaluate `$public_request` where you decide
whether a request needs to authenticate, and let it pass if it is `1`.

### Passing on the visitor's IP address

Ticket requests are accepted without authentication, so the traffic limiter of
PrivateBin is their main protection against abuse. Behind a reverse proxy it
only works if PrivateBin knows the IP address of the visitor. Otherwise, all
requests appear to come from the proxy and share a single limit.

In NGINX, pass the address in a header:

```nginx
location / {
    # ...
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_pass http://privatebin-backend;
}
```

Then tell PrivateBin which header to read, in the `[traffic]` section of
`cfg/conf.php`. The option is documented in `cfg/conf.sample.php`:

```ini
[traffic]
; set the HTTP header containing the visitors IP address
header = "X_FORWARDED_FOR"
```

Notes:

* The header name is written without the `HTTP_` prefix and with underscores,
  as PHP exposes it: `X-Forwarded-For` becomes `X_FORWARDED_FOR`.
* Set the header to `$remote_addr`, which replaces any value sent by the
  client. Don't use `$proxy_add_x_forwarded_for` here: it appends to a value
  supplied by the client, which then can be made up to avoid the limit, and it
  can contain a list of addresses, where PrivateBin expects a single one.
* If NGINX itself sits behind another proxy or load balancer, first restore
  the visitor's address using the
  [realip module](https://nginx.org/en/docs/http/ngx_http_realip_module.html)
  (`set_real_ip_from` and `real_ip_header`), so that `$remote_addr` holds the
  visitor's address and not that of the proxy in front.
* Only enable the option if all requests really pass through such a proxy. If
  PrivateBin is reachable directly as well, visitors can send the header
  themselves.

### Limit the size of ticket requests

This step is required, not optional. The ticket request is accepted without
authentication, and NGINX writes the request body to disk before PrivateBin
sees it. The `sizelimit` of PrivateBin is only checked afterwards, so a large
`client_max_body_size`, i.e. one that you set for uploading files when
authenticated, would let anybody send that much data to your server.
`client_max_body_size` can't depend on a variable, so route the ticket requests
to a `location` of their own, with a small limit:

```nginx
map "$request_method:$request_uri" $ticket_request {
    default                              0;
    "~^POST:/\?ticket=[0-9a-f]{16}$"     1;
}

server {
    # ...
    location / {
        # hand ticket requests to the location below, the query string is kept
        if ($ticket_request) {
            rewrite ^ /_ticket last;
        }

        # ... authentication and proxy_pass as before, with your large limit
    }

    # only reachable through the rewrite above, not from the outside
    location = /_ticket {
        internal;
        # the `sizelimit` of PrivateBin, plus about 40% for the encoding
        client_max_body_size 15m;

        proxy_set_header Host              $host;
        proxy_set_header X-Forwarded-For   $remote_addr;
        proxy_pass http://privatebin-backend/;
    }
}
```

Adapt the proxy headers to whatever your regular `location` sets. The default
`sizelimit` is 10 MiB, which fits within 15 MiB once encoded. If you raise
`sizelimit`, raise this value too. The `rewrite` happens before the
authentication check, so the internal location needs no authentication of its
own, while the `$public_request` map keeps controlling everything else. Request
the page with a ticket you made and check the access log to see that the
request was served by this location.

### Hardening

* **Rate limiting.** Consider `limit_req` for unauthenticated POST requests,
  in addition to the traffic limiter of PrivateBin.
* **Ticket lifetime.** Tickets live as long as the expiration chosen when they
  are created. Choose short ones for tickets sent over untrusted channels.
* **Logging.** The ticket ID appears in access logs of the proxy as part of
  the query string, the key never does, as it is part of the URL fragment.

### Other proxies

The same rule applies to Apache, Caddy, Traefik, oauth2-proxy and the like:
allow `POST` requests to `/` with a query string that is exactly
`ticket=<16 hexadecimal characters>` without authentication. Check how your
proxy matches rules: some only compare the path and ignore the query string,
in which case this rule can't be expressed there and needs to be handled by the
proxy in front of it.

## Troubleshooting

* **`nginx: [emerg] zero size shared memory zone "privatebin_ticket"`.** A
  `limit_req` refers to a zone that isn't declared. Make sure the
  `limit_req_zone ... zone=privatebin_ticket:10m ...` line is loaded, in the
  `http` context and not inside `server` or `location`, and that the name is
  spelled the same in both places. `nginx -T | grep limit_req_zone` shows if
  the running configuration contains it.
* **The One-time ticket button is missing.** The feature isn't enabled. Check
  `onetimeticket = true` in the `[main]` section of `cfg/conf.php`. The button
  is only shown on the create page, not while viewing a document.
* **The button is greyed out.** The editor or the file list isn't empty.
* **"A one-time ticket needs an expiration time."** Select an expiration other
  than "never".
* **The recipient can open the link but sending fails.** The proxy rejects the
  `POST /?ticket=<ticket id>` request, check that the rule above matches. The
  request shows up in the proxy's access log with the status code it was
  rejected with, usually 401 or 403.
* **The recipient sees "Document does not exist, has expired or has been
  deleted".** The ticket was already used or has expired. Create a new one.
