<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Setup;

use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\TextConfigOption;

class ConfigOptionsList implements ConfigOptionsListInterface
{
    public const SEARCH_ENGINE = 'lupasearch';

    public const INPUT_KEY_LUPASEARCH_API_KEY = 'lupasearch-api-key';

    public const INPUT_KEY_LUPASEARCH_EMAIL = 'lupasearch-email';

    public const INPUT_KEY_LUPASEARCH_PASSWORD = 'lupasearch-password';

    public const INPUT_KEY_LUPASEARCH_PRODUCT_INDEX = 'lupasearch-product-index';

    public const INPUT_KEY_LUPASEARCH_CATEGORY_INDEX = 'lupasearch-category-index';

    public const INPUT_KEY_LUPASEARCH_PRODUCT_SUGGESTION_INDEX = 'lupasearch-product-suggestion-index';

    public const INPUT_KEY_LUPASEARCH_CATEGORY_SUGGESTION_INDEX = 'lupasearch-category-suggestion-index';

    public const MAPPING_INPUT_KEY_TO_CONFIG_PATH = [
        self::INPUT_KEY_LUPASEARCH_API_KEY => 'lupasearch/general/api_key',
        self::INPUT_KEY_LUPASEARCH_EMAIL => 'lupasearch/general/email',
        self::INPUT_KEY_LUPASEARCH_PASSWORD => 'lupasearch/general/password',
        self::INPUT_KEY_LUPASEARCH_PRODUCT_INDEX => 'lupasearch/indices/product',
        self::INPUT_KEY_LUPASEARCH_CATEGORY_INDEX => 'lupasearch/indices/category',
        self::INPUT_KEY_LUPASEARCH_PRODUCT_SUGGESTION_INDEX => 'lupasearch/indices/product_suggestion',
        self::INPUT_KEY_LUPASEARCH_CATEGORY_SUGGESTION_INDEX => 'lupasearch/indices/category_suggestion'
    ];

    /**
     * @inheritDoc
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function getOptions(): array
    {
        return [
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_API_KEY,
                TextConfigOption::FRONTEND_WIZARD_PASSWORD,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_API_KEY],
                'Lupasearch API Key.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_EMAIL,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_EMAIL],
                'Lupasearch Account Email.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_PASSWORD,
                TextConfigOption::FRONTEND_WIZARD_PASSWORD,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_PASSWORD],
                'Lupasearch Account Password.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_PRODUCT_INDEX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_PRODUCT_INDEX],
                'Lupasearch Product Index.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_CATEGORY_INDEX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_CATEGORY_INDEX],
                'Lupasearch Category Index.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_PRODUCT_SUGGESTION_INDEX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_PRODUCT_SUGGESTION_INDEX],
                'Lupasearch Product Suggestion Index.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_LUPASEARCH_CATEGORY_SUGGESTION_INDEX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::MAPPING_INPUT_KEY_TO_CONFIG_PATH[self::INPUT_KEY_LUPASEARCH_CATEGORY_SUGGESTION_INDEX],
                'Lupasearch Category Suggestion Index.'
            )
        ];
    }

    /**
     * @inheritDoc
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        foreach (self::MAPPING_INPUT_KEY_TO_CONFIG_PATH as $inputKey => $configPath) {
            $value = $options[$inputKey] ?? null;

            if (!$value) {
                continue;
            }

            $configData->set($configPath, $options[$inputKey]);
        }

        return $configData;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        if (self::SEARCH_ENGINE !== $deploymentConfig->get(EngineInterface::CONFIG_ENGINE_PATH)) {
            return [];
        }

        $errors = [];

        if (
            !isset($options[self::INPUT_KEY_LUPASEARCH_API_KEY]) &&
            (!isset($options[self::INPUT_KEY_LUPASEARCH_EMAIL]) ||
                !isset($options[self::INPUT_KEY_LUPASEARCH_PASSWORD]))
        ) {
            $errors[] = 'Lupasearch API Key or Email&Password is required.';
        }

        if (!isset($options[self::INPUT_KEY_LUPASEARCH_PRODUCT_INDEX])) {
            $errors[] = 'Lupasearch Product Index is required.';
        }

        return $errors;
    }
}
