# LupaSearch Magento 2 Plugin

## Introduction

The LupaSearch Magento 2 Plugin integrates LupaSearch's search functionality into your Magento 2 store, providing enhanced search capabilities that improve the user experience.

## Requirements

- **PHP**: >=7.4
- **Magento 2**: compatible with Magento 2.2.x - 2.4.x
- **Optional**: For MySQL-based queues, install the [lupasearch/magento2-lupasearch-plugin-queue-db](https://github.com/lupasearch/magento2-plugin-queue-db) extension.

## Installation

### 1. Install with Composer

Install the core LupaSearch plugin:

```bash
composer require lupasearch/magento2-lupasearch-plugin
```

If RabbitMQ is not available, include the MySQL queue compatibility extension:

```
composer require lupasearch/magento2-lupasearch-plugin-queue-db
```

### 2. Enable modules

```
php bin/magento module:enable LupaSearch_LupaSearchPluginCore LupaSearch_LupaSearchPlugin
```

If the MySQL queue compatibility extension is installed, enable it as well:

```
php bin/magento module:enable LupaSearch_LupaSearchPluginQueueDb
```

### 3. Run installation scripts

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

Change Indexer Mode to "On Schedule" (only works on this mode)

```shell
bin/magento indexer:set-mode schedule lupasearch_product lupasearch_category
```

Enable cache

```shell
bin/magento cache:enable lupasearch
```

> 💡 **Before indexing, make sure your index mappings are already configured.**
> If your product, category, or suggestion mappings do not exist yet, use the examples available here: https://github.com/lupasearch/magento2-plugin/tree/main/examples/mapping
>
> To update a mapping, use the mapping endpoints available in the LupaSearch API: https://console.lupasearch.com/docs/api#tag/Mapping

### 4. Configure the extension

#### 4.1. Initial configurations

Navigate to **Stores -> Configuration -> Catalog -> LupaSearch** in your Magento admin panel.

Configure the following settings:

- **General configuration > API Key**: Generate an API Key in the LupaSearch Dashboard and paste it into this field.
- **Indices > Product**: Copy the UUID of your product search index from the LupaSearch Dashboard and paste it here.
- **Indices > Product Suggestion**: Copy the UUID of your product suggestion index and paste it here.
- **Indices > Category**: Copy the UUID of your category search index and paste it here.
- **Indexing > Enabled**: Set this option to **Yes**.

Click **Save Config** to apply your changes.

#### 4.2. Generate search configurations

After saving the initial settings, go to the **LupaSearch Queries Management** section in your Magento admin panel.
Click the **Generate All Queries** button.
A confirmation message, **"Queries successfully generated,"** will appear once the process is complete.
To verify, go to the **Search Queries** page in the LupaSearch Dashboard and confirm that all queries are created successfully.

### 5. Indexing

Reindex the data:

```
bin/magento index:reindex lupasearch_product
bin/magento index:reindex lupasearch_category
```

Manually run the indexer to refresh the data:

```shell
bin/magento indexer:reindex lupasearch_product
bin/magento indexer:reindex lupasearch_category
```

### 6. Run consumers

Start the LupaSearch queue consumers to process queued tasks:

```
bin/magento queue:consumers:start lupasearch.all
```

### 7: Verify data

1. Log in to the [LupaSearch Console](https://console.lupasearch.com/login).
2. Navigate to your product index.
3. Verify that your products and categories are correctly indexed and available.

## Adding Custom Product Attributes

To add custom attributes to the product feed, follow these steps:

1. Review existing Hydrators used in the plugin:
   https://github.com/lupasearch/magento2-plugin/tree/main/Model/Product/Hydrator

2. Create your own Hydrator class implementing the logic for your custom attribute.

3. Register the new Hydrator in di.xml by adding it to the hydrator pool, for example:
   https://github.com/lupasearch/magento2-plugin/blob/main/etc/di.xml#L476

4. Go to your index mapping and add the new attribute field.

5. Add the new attribute to your search query configuration.

## Support

For questions or assistance, contact us at [support@lupasearch.com](mailto:support@lupasearch.com).
