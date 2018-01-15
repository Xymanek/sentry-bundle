<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Xymanek\SentryBundle\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * Return the instance of ConfigurationInterface that should be used by the
     * Configuration-specific assertions in this test-case
     *
     * @return \Symfony\Component\Config\Definition\ConfigurationInterface
     */
    protected function getConfiguration ()
    {
        return new Configuration();
    }

    public function testDecoratedWithoutFilters ()
    {
        $this->assertProcessedConfigurationEquals(
            [
                [
                    'filters' => [
                        'sentry' => null
                    ]
                ]
            ],
            [
                'filters' => [
                    'sentry' => [
                        'filter_exceptions' => []
                    ]
                ]
            ],
            'filters'
        );
    }

    public function testClassNormalization ()
    {
        $this->assertProcessedConfigurationEquals(
            [
                [
                    'filters' => [
                        'sentry' => [
                            'filter_exceptions' => [
                                '\\Namespace\\SomeClass',
                                'Namespace\\AnotherClass',
                                '\\GlobalClass',
                                'AnotherGlobalClass',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'filters' => [
                    'sentry' => [
                        'filter_exceptions' => [
                            'Namespace\\SomeClass',
                            'Namespace\\AnotherClass',
                            'GlobalClass',
                            'AnotherGlobalClass',
                        ]
                    ]
                ]
            ],
            'filters.*.filter_exceptions'
        );
    }

    public function testFilterExceptionDisabled ()
    {
        $expected = [
            'filters' => [
                'sentry' => [
                    'filter_exceptions' => []
                ]
            ]
        ];

        $this->assertProcessedConfigurationEquals(
            [
                [
                    'filters' => [
                        'sentry' => [
                            'filter_exceptions' => false
                        ]
                    ]
                ]
            ],
            $expected,
            'filters.*.filter_exceptions'
        );

        $this->assertProcessedConfigurationEquals(
            [
                [
                    'filters' => [
                        'sentry' => [
                            'filter_exceptions' => null
                        ]
                    ]
                ]
            ],
            $expected,
            'filters.*.filter_exceptions'
        );
    }
}
