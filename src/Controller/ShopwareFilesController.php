<?php

namespace Frosh\Tools\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Kernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(path="/api/_action/frosh-tools")
 */
class ShopwareFilesController
{
    private string $shopwareVersion;

    private string $projectDir;

    public function __construct(string $shopwareVersion, string $projectDir)
    {
        $this->shopwareVersion = $shopwareVersion;
        $this->projectDir = $projectDir;
    }

    /**
     * @Route(path="/shopware-files", methods={"GET"}, name="api.frosh.tools.shopware-files")
     */
    public function listShopwareFiles(): JsonResponse
    {
        if ($this->shopwareVersion === Kernel::SHOPWARE_FALLBACK_VERSION) {
            return new JsonResponse(['error' => 'Git version is not supported']);
        }

        if (!file_exists($this->projectDir . '/vendor/shopware/core')) {
            return new JsonResponse(['error' => 'Works only in Production template']);
        }

        $url = sprintf('https://swagger.docs.fos.gg/version/%s/Files.md5sums', $this->shopwareVersion);

        $data = trim(@file_get_contents($url));

        if (empty($data)) {
            return new JsonResponse(['error' => 'No file information for this Shopware version']);
        }

        $invalidFiles = [];

        foreach (explode("\n", $data) as $row) {
            [$expectedMd5Sum, $file] = explode('  ', trim($row));
            $path = $this->projectDir . '/' . $file;

            if (!is_file($path)) {
                continue;
            }

            $md5Sum = md5_file($path);

            if ($this->isIgnoredFileHash($file, $md5Sum)) {
                continue;
            }

            if ($md5Sum !== $expectedMd5Sum) {
                $invalidFiles[] = [
                    'name' => $file,
                    'shopwareUrl' => $this->getShopwareUrl($file),
                    'content' => file_get_contents($path),
                    'originalContent' => $this->getOriginalFileContent($file)
                ];
            }
        }

        if (empty($invalidFiles)) {
            return new JsonResponse(['ok' => true]);
        }

        return new JsonResponse(['ok' => false, 'files' => $invalidFiles]);
    }

    /**
     * @Route(path="/shopware-file/restore", methods={"GET"}, name="api.frosh.tools.shopware-file.restore")
     */
    public function restoreShopwareFile(Request $request): JsonResponse
    {
        if ($this->shopwareVersion === Kernel::SHOPWARE_FALLBACK_VERSION) {
            return new JsonResponse(['error' => 'Git version is not supported'], );
        }

        if (!file_exists($this->projectDir . '/vendor/shopware/core')) {
            return new JsonResponse(['error' => 'Works only in Production template']);
        }

        $file = $request->query->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'no file provided']);
        }

        $path = realpath($this->projectDir . '/' . $file);
        if ($path === false || !str_starts_with($path, $this->projectDir) || !is_file($path)) {
            return new JsonResponse(['error' => 'File is invalid']);
        }

        file_put_contents($path, $this->getOriginalFileContent($file));

        return new JsonResponse(['status' => sprintf('File at "%s" has been restored', $file)]);
    }

    private function getShopwareUrl(string $name): ?string
    {
        $name = preg_replace('/^vendor\/shopware\//', '', $name);
        return 'https://github.com/shopware/platform/blob/v' . $this->shopwareVersion . '/src/' . ucfirst($name);
    }

    private function getOriginalFileContent(string $name): ?string
    {
        return file_get_contents($this->getShopwareUrl($name) . '?raw=true');
    }

    private function isIgnoredFileHash(string $file, string $md5Sum): bool
    {
        // This file differs on update systems. This change is missing in update packages lol!
        // @see: https://github.com/shopware/platform/commit/957e605c96feef67a6c759f00c58e35d2d1ac84f#diff-e49288a50f0d7d8acdabb5ffef2edcd5ac4f4126f764d3153d19913ce98aba1cL10-R80
        // @see: https://issues.shopware.com/issues/NEXT-11618
        if ($file === 'vendor/shopware/core/Checkout/Order/Aggregate/OrderAddress/OrderAddressDefinition.php' &&
            $md5Sum === 'e3da59baff091fd044a12a61cd445385') {
            return true;
        }

        // This file differs on update systems. This change is missing in update packages lol!
        // @see: https://github.com/shopware/platform/commit/bbdcbe254e3239e92eb1f71a7afedfb94b7fb150
        // @see: https://issues.shopware.com/issues/NEXT-11775
        if ($file === 'vendor/shopware/administration/Resources/app/administration/src/app/component/media/sw-media-compact-upload-v2/index.js' &&
            $md5Sum === '74d18e580ffe87559e6501627090efb3') {
            return true;
        }

        return false;
    }
}
