<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\Catalog\Model\Product\Option\DataProvider\Type;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product\Option;

/**
 * Data provider for options from file group with type "file".
 */
class File extends AbstractBase
{
    /**
     * @inheritdoc
     */
    public function getDataForCreateOptions(): array
    {
        return $this->injectFileExtension(
            array_merge_recursive(
                parent::getDataForCreateOptions(),
                [
                    "type_{$this->getType()}_option_file_extension" => [
                        [
                            'record_id' => 0,
                            'sort_order' => 1,
                            'is_require' => 1,
                            'sku' => 'test-option-title-1',
                            'max_characters' => 30,
                            'title' => 'Test option title 1',
                            'type' => $this->getType(),
                            'price' => 10,
                            'price_type' => 'fixed',
                            'file_extension' => 'gif',
                            'image_size_x' => 10,
                            'image_size_y' => 20,
                        ],
                    ],
                    "type_{$this->getType()}_option_maximum_file_size" => [
                        [
                            'record_id' => 0,
                            'sort_order' => 1,
                            'is_require' => 1,
                            'sku' => 'test-option-title-1',
                            'title' => 'Test option title 1',
                            'type' => $this->getType(),
                            'price' => 10,
                            'price_type' => 'fixed',
                            'file_extension' => 'gif',
                            'image_size_x' => 10,
                            'image_size_y' => 20,
                        ],
                    ],
                ]
            ),
            'png'
        );
    }

    /**
     * @inheritdoc
     */
    public function getDataForUpdateOptions(): array
    {
        return $this->injectFileExtension(
            array_merge_recursive(
                parent::getDataForUpdateOptions(),
                [
                    "type_{$this->getType()}_option_file_extension" => [
                        [
                            'file_extension' => 'jpg',
                        ],
                    ],
                    "type_{$this->getType()}_option_maximum_file_size" => [
                        [
                            'image_size_x' => 300,
                            'image_size_y' => 815,
                        ],
                    ],
                ]
            ),
            ''
        );
    }

    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return ProductCustomOptionInterface::OPTION_TYPE_FILE;
    }

    /**
     * Add 'file_extension' value to each option.
     *
     * @param array $data
     * @param string $extension
     * @return array
     */
    private function injectFileExtension(array $data, string $extension): array
    {
        foreach ($data as &$caseData) {
            foreach ($caseData as &$option) {
                if (!isset($option[Option::KEY_FILE_EXTENSION])) {
                    $option[Option::KEY_FILE_EXTENSION] = $extension;
                }
            }
        }

        return $data;
    }
}
