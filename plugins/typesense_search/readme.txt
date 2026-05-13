ResourceSpace Typesense Search Plugin
=====================================

Overview
--------

The typesense_search plugin integrates ResourceSpace with a local Typesense
server to provide significantly faster keyword searching on large datasets.

The plugin intercepts compatible ResourceSpace searches and routes them to
Typesense. Unsupported searches automatically fall back to the normal
ResourceSpace/MySQL search engine.

Current supported searches:
- Standard keyword searches
- Resource type filtering
- Archive state filtering

Current unsupported searches (fallback to MySQL):
- Searches beginning with !
- Field searches using :
- Node searches (@@)
- Smart search
- Editable-only searches
- SQL-return mode
- Disk usage requests

Requirements
------------

- Ubuntu 22.04 or newer recommended
- ResourceSpace
- PHP cURL extension
- Typesense server 0.25+ recommended

Installing Typesense on Ubuntu
------------------------------

1. Install required packages:

    sudo apt update
    sudo apt install curl gnupg -y

2. Add the Typesense repository key:

    curl -fsSL https://dl.typesense.org/releases/typesense-0.25.2/typesense-server-0.25.2-amd64.deb -o typesense-server.deb

3. Install Typesense:

    sudo dpkg -i typesense-server.deb

4. Enable and start the service:

    sudo systemctl enable typesense-server
    sudo systemctl start typesense-server

5. Verify the service is running:

    curl http://127.0.0.1:8108/health

Expected response:

    {"ok":true}

Plugin Installation
-------------------

1. Ensure using the correct branch

2. Enable the plugin in ResourceSpace.

3. Configure the plugin settings via the plugin setup page. Examples:

    Host: 127.0.0.1
    Port: 8108
    Protocol: http
    API key: YOUR_SECURE_API_KEY
    Collection: resources
    Timeout seconds: 2

4. Ensure PHP cURL is enabled:

    php -m | grep curl

If curl is missing:

    sudo apt install php-curl
    sudo systemctl restart apache2

Initial Indexing
----------------

The plugin includes a CLI reindex script:

    plugins/typesense_search/scripts/reindex.php

Run indexing from the ResourceSpace root directory:

    php plugins/typesense_search/scripts/reindex.php

Optional arguments:

    php plugins/typesense_search/scripts/reindex.php [batch_size] [start_after_ref]

Examples:

    php plugins/typesense_search/scripts/reindex.php 500

    php plugins/typesense_search/scripts/reindex.php 500 100000

The script outputs:
- Indexed resources
- Failed resources
- Batch timing
- Indexing rate
- Content size indexed
- Memory usage

Example output:

    Starting Typesense reindex | Batch size: 500 | Starting after ref: 0

    [2026-05-12 15:10:42]
    Indexed this batch: 500
    | Failed this batch: 0
    | Total indexed: 500
    | Last ref: 500
    | Batch content: 1,284,221 chars
    | Total content: 1,284,221 chars
    | Batch time: 4.21s
    | Rate: 118.77 resources/sec
    | Memory: 64MB

Testing Search
--------------

After indexing:

1. Log into ResourceSpace
2. Run a normal keyword search
3. Verify results appear normally

Debugging
---------

ResourceSpace debug logging can help troubleshoot indexing/search issues.

Useful checks:

Verify Typesense health:

    curl http://127.0.0.1:8108/health

Verify collection exists:

    curl -H "X-TYPESENSE-API-KEY: YOUR_SECURE_API_KEY" \
    http://127.0.0.1:8108/collections

Verify indexed documents:

    curl -H "X-TYPESENSE-API-KEY: YOUR_SECURE_API_KEY" \
    "http://127.0.0.1:8108/collections/resources/documents/search?q=test&query_by=title,text"

Metadata Updates
----------------

The plugin automatically reindexes resources when metadata changes.

Node changes also trigger reindexing of affected resources.

Current Architecture
--------------------

- ResourceSpace plugin hook integration
- Automatic fallback to MySQL search
- Typesense returns ordered resource refs
- ResourceSpace hydrates refs into standard result rows
- Preserves normal ResourceSpace permissions and access filtering

Notes
-----

This plugin currently focuses on accelerating standard keyword searches.

Future enhancements may include:
- Native node searching
- Native field search support
- Faceting/filtering
- Incremental indexing queue
- Access-aware indexing
- Real-time background indexing