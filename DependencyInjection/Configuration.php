<?php
namespace Xymanek\SentryBundle\DependencyInjection;

use Raven_Client;
use Raven_Compat;
use Raven_Processor_SanitizeDataProcessor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('xymanek_sentry');

        // Basic Sentry configuration
        $rootNode
            ->children()
                ->scalarNode('dsn')->defaultNull()->end()
            ->end();

        // Sentry client options
        $rootNode
            ->children()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('logger')->defaultValue('php')->end()
                        ->scalarNode('server')->defaultNull()->end()
                        ->scalarNode('secret_key')->defaultNull()->end()
                        ->scalarNode('public_key')->defaultNull()->end()
                        ->scalarNode('project')->defaultValue(1)->end()
                        ->booleanNode('auto_log_stacks')->defaultFalse()->end()
                        ->scalarNode('name')->defaultValue(Raven_Compat::gethostname())->end()
                        ->scalarNode('site')->defaultNull()->end() //
                        ->arrayNode('tags')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('release')->defaultNull()->end()
                        ->scalarNode('environment')->defaultValue('%kernel.environment%')->end()
                        ->scalarNode('sample_rate')->defaultValue(1)->end()
                        ->booleanNode('trace')->defaultTrue()->end()
                        ->scalarNode('timeout')->defaultValue(2)->end()
                        ->scalarNode('message_limit')->defaultValue(Raven_Client::MESSAGE_LIMIT)->end()
                        ->arrayNode('exclude')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('http_proxy')->defaultNull()->end()
                        ->arrayNode('extra')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('curl_method')->defaultValue('sync')->end()
                        ->scalarNode('curl_path')->defaultValue('curl')->end()
                        ->booleanNode('curl_ipv4')->defaultTrue()->end()
                        ->scalarNode('ca_cert')->defaultNull()->end()
                        ->booleanNode('verify_ssl')->defaultTrue()->end()
                        ->scalarNode('curl_ssl_version')->defaultNull()->end()
                        //->scalarNode('trust_x_forwarded_proto')->defaultFalse()->end()
                        ->scalarNode('mb_detect_order')->defaultNull()->end()
                        ->scalarNode('error_types')->defaultNull()->end()
                        ->scalarNode('app_path')
                            ->defaultValue('%kernel.root_dir%/..')
                            ->info('app path is used to determine if code is part of your application')
                        ->end()
                        ->arrayNode('excluded_app_paths')
                            ->defaultValue([
                                '%kernel.root_dir%/../vendor',
                                '%kernel.root_dir%/../app/cache',
                                '%kernel.root_dir%/../var/cache',
                            ])
                            ->prototype('scalar')->end()
                            ->treatNullLike([])
                        ->end()
                        ->arrayNode('prefixes')
                            ->defaultValue(['%kernel.root_dir%/..'])
                            ->treatNullLike([])
                            ->prototype('scalar')->end()
                            ->info('a list of prefixes used to coerce absolute paths into relative')
                        ->end()
                        ->booleanNode('install_default_breadcrumb_handlers')->defaultFalse()->end() // Default changed
                        ->booleanNode('install_shutdown_handler')->defaultTrue()->end()
                        ->arrayNode('processors')
                            ->defaultValue([Raven_Processor_SanitizeDataProcessor::class])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('processorOptions')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end();

        // Bundle-specific configuration
        $rootNode
            ->children()
				->arrayNode('context_providers')
                    ->treatNullLike([
						'token' => false,
						'role_hierarchy' => false,
						'client_ip' => false,
						'session' => false,
					])
                    ->treatFalseLike([
						'token' => false,
						'role_hierarchy' => false,
						'client_ip' => false,
						'session' => false,
					])
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('token')->defaultTrue()->end()
                        ->booleanNode('role_hierarchy')->defaultTrue()->end()
                        ->booleanNode('client_ip')->defaultTrue()->end()
                        ->booleanNode('session')->defaultTrue()->end()
					->end()
				->end()
            ->end();

		// Record filter feature
        $rootNode
            ->children()
                ->arrayNode('filters')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('filter_exceptions')
                                ->defaultValue([])
                                ->treatNullLike([])
                                ->treatFalseLike([])
                                ->scalarPrototype()
                                    ->beforeNormalization()
                                        ->ifTrue(function ($value) {
                                            return $value[0] === '\\';
                                        })
                                        ->then(function ($value) {
                                            return substr($value, 1);
                                        })
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
