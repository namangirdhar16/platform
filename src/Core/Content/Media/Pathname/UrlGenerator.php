<?php declare(strict_types=1);

namespace Shopware\Core\Content\Media\Pathname;

use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\MediaStruct;
use Shopware\Core\Content\Media\Pathname\PathnameStrategy\PathnameStrategyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $baseUrl = null;

    /**
     * @var PathnameStrategyInterface
     */
    private $strategy;

    public function __construct(
        PathnameStrategyInterface $strategy,
        RequestStack $requestStack,
        string $baseUrl = null
    ) {
        $this->strategy = $strategy;
        $this->requestStack = $requestStack;

        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
    }

    /**
     * @throws EmptyMediaFilenameException
     */
    public function getRelativeMediaUrl(MediaStruct $media): string
    {
        $physicalFileName = $media->getFileName();

        if ($media->getUploadedAt() !== null) {
            $physicalFileName = sprintf('%d/%s', $media->getUploadedAt()->getTimestamp(), $physicalFileName);
        }
        $encodedFileName = $this->encodeFilename($physicalFileName);

        $extension = $media->getFileExtension() ? '.' . $media->getFileExtension() : '';

        return 'media/' . $encodedFileName . $extension;
    }

    /**
     * @throws EmptyMediaFilenameException
     */
    public function getAbsoluteMediaUrl(MediaStruct $media): string
    {
        return $this->getBaseUrl() . '/' . $this->getRelativeMediaUrl($media);
    }

    /**
     * @throws EmptyMediaFilenameException
     *
     * @return string
     */
    public function getRelativeThumbnailUrl(MediaStruct $media, int $width, int $height, bool $isHighDpi = false): string
    {
        $mediaPathInfo = pathinfo($this->getRelativeMediaUrl($media));
        $mediaPathInfo['dirname'] = preg_replace('/^media/', 'thumbnail', $mediaPathInfo['dirname']);

        $thumbnailExtension = "_${width}x${height}";
        if ($isHighDpi) {
            $thumbnailExtension .= '@2x';
        }

        $extension = isset($mediaPathInfo['extension']) ? '.' . $mediaPathInfo['extension'] : '';

        return $mediaPathInfo['dirname'] . '/' . $mediaPathInfo['filename'] . $thumbnailExtension . $extension;
    }

    /**
     * @throws EmptyMediaFilenameException
     */
    public function getAbsoluteThumbnailUrl(MediaStruct $media, int $width, int $height, bool $isHighDpi = false): string
    {
        return $this->getBaseUrl() . '/' . $this->getRelativeThumbnailUrl($media, $width, $height, $isHighDpi);
    }

    private function normalizeBaseUrl($baseUrl): ?string
    {
        if (!$baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/');
    }

    private function getBaseUrl(): string
    {
        if (!$this->baseUrl) {
            $this->baseUrl = $this->createFallbackUrl();
        }

        return $this->baseUrl;
    }

    private function createFallbackUrl(): string
    {
        $request = $this->requestStack->getMasterRequest();
        if ($request) {
            $basePath = $request->getSchemeAndHttpHost() . $request->getBasePath();

            return rtrim($basePath, '/');
        }

        //todo@next: resolve default shop path
        return '';
    }

    /**
     * @throws EmptyMediaFilenameException
     */
    private function encodeFilename(?string $filename): string
    {
        if (empty($filename)) {
            throw new EmptyMediaFilenameException();
        }

        return $this->strategy->encode($filename);
    }
}
