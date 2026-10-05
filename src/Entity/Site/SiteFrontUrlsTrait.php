<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Front URLs of a BaseSite (`site_front_urls`).
 */
trait SiteFrontUrlsTrait
{
    /**
     * @var Collection<int, SiteFrontUrl>
     */
    protected Collection $frontUrls;

    public function getFrontUrls(): Collection
    {
        return $this->frontUrls;
    }

    #[Groups([BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    #[SerializedName('front_urls')]
    public function getFrontUrlValues(): array
    {
        $urls = [];

        foreach ($this->frontUrls as $frontUrl) {
            $urls[] = $frontUrl->getUrl();
        }

        return $urls;
    }

    public function setFrontUrlValues(iterable $urls): void
    {
        $values = [];

        foreach ($urls as $url) {
            $url = trim($url);

            if ('' !== $url) {
                $values[$url] = $url;
            }
        }

        foreach ($this->frontUrls as $frontUrl) {
            if (!isset($values[$frontUrl->getUrl()])) {
                $this->frontUrls->removeElement($frontUrl);
            }

            unset($values[$frontUrl->getUrl()]);
        }

        foreach ($values as $url) {
            $this->frontUrls->add(new SiteFrontUrl($this, $url));
        }
    }
}
