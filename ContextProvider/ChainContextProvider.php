<?php
namespace Xymanek\SentryBundle\ContextProvider;

class ChainContextProvider implements UserContextProviderInterface, ExtraContextProviderInterface, TagsProviderInterface
{
    /**
     * @var UserContextProviderInterface[]
     */
    protected $userContextProviders = [];

    /**
     * @var ExtraContextProviderInterface[]
     */
    protected $extraContextProviders = [];

    /**
     * @var TagsProviderInterface[]
     */
    protected $tagsProviders = [];

    public function addUserContextProvider (UserContextProviderInterface $provider)
    {
        $this->userContextProviders[] = $provider;
    }

    public function addExtraContextProvider (ExtraContextProviderInterface $provider)
    {
        $this->extraContextProviders[] = $provider;
    }

    public function addTagsProvider (TagsProviderInterface $provider)
    {
        $this->tagsProviders[] = $provider;
    }

    public function getUserData (): array
    {
        $data = [];

        foreach ($this->userContextProviders as $provider) {
            $data = array_merge($data, $provider->getUserData());
        }

        return $data;
    }

    public function getExtraData (): array
    {
        $data = [];

        foreach ($this->extraContextProviders as $provider) {
            $data = array_merge($data, $provider->getExtraData());
        }

        return $data;
    }

    public function getSentryTags (): array
    {
        $tags = [];

        foreach ($this->tagsProviders as $provider) {
            $tags = array_merge($tags, $provider->getSentryTags());
        }

        return $tags;
    }
}