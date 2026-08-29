<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Foundation\Http\Request;

/**
 * Class ImageValidator
 *
 * Validates uploaded image files with support for:
 * - Extensive MIME type mapping (200+ format extension combinations)
 * - File extension verification
 * - Dimension constraint validation (min/max width and height)
 * - Aspect ratio enforcement (exact, minimum, and maximum limits)
 * - Safe image integrity verification via `getimagesize()`
 * - Detailed error handling and customizable error messaging
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class ImageValidator extends FileValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'image';

    /**
     * Internal array containing error messages captured during validation.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Parsed image metadata (width, height, mime, type).
     *
     * @var array<string, mixed>
     */
    protected array $imageInfo = [];

    /**
     * Map of supported image MIME types to their corresponding valid file extensions.
     *
     * @var array<string, array<int, string>>
     */
    private const MAP = [
        'image/aces' => ['exr'],
        'image/apng' => ['apng', 'png'],
        'image/astc' => ['astc'],
        'image/avci' => ['avci'],
        'image/avcs' => ['avcs'],
        'image/avif' => ['avif', 'avifs'],
        'image/avif-sequence' => ['avif', 'avifs'],
        'image/bmp' => ['bmp', 'dib'],
        'image/cdr' => ['cdr'],
        'image/cgm' => ['cgm'],
        'image/dicom-rle' => ['drle'],
        'image/dpx' => ['dpx'],
        'image/emf' => ['emf'],
        'image/fax-g3' => ['g3'],
        'image/fits' => ['fits', 'fit', 'fts'],
        'image/g3fax' => ['g3'],
        'image/gif' => ['gif'],
        'image/heic' => ['heic', 'heif', 'hif'],
        'image/heic-sequence' => ['heics', 'heic', 'heif', 'hif'],
        'image/heif' => ['heif', 'heic', 'hif'],
        'image/heif-sequence' => ['heifs', 'heic', 'heif', 'hif'],
        'image/hej2k' => ['hej2'],
        'image/hsj2' => ['hsj2'],
        'image/ico' => ['ico'],
        'image/icon' => ['ico'],
        'image/ief' => ['ief'],
        'image/jls' => ['jls'],
        'image/jp2' => ['jp2', 'jpg2'],
        'image/jpeg' => ['jpg', 'jpeg', 'jpe', 'jfif'],
        'image/jpeg2000' => ['jp2', 'jpg2'],
        'image/jpeg2000-image' => ['jp2', 'jpg2'],
        'image/jph' => ['jph'],
        'image/jphc' => ['jhc'],
        'image/jpm' => ['jpm', 'jpgm'],
        'image/jpx' => ['jpx', 'jpf'],
        'image/jxl' => ['jxl'],
        'image/jxr' => ['jxr', 'hdp', 'wdp'],
        'image/jxra' => ['jxra'],
        'image/jxrs' => ['jxrs'],
        'image/jxs' => ['jxs'],
        'image/jxsc' => ['jxsc'],
        'image/jxsi' => ['jxsi'],
        'image/jxss' => ['jxss'],
        'image/ktx' => ['ktx'],
        'image/ktx2' => ['ktx2'],
        'image/openraster' => ['ora'],
        'image/pdf' => ['pdf'],
        'image/photoshop' => ['psd'],
        'image/pjpeg' => ['jpg', 'jpeg', 'jpe', 'jfif'],
        'image/png' => ['png'],
        'image/prs.btif' => ['btif', 'btf'],
        'image/prs.pti' => ['pti'],
        'image/psd' => ['psd'],
        'image/qoi' => ['qoi'],
        'image/rle' => ['rle'],
        'image/sgi' => ['sgi'],
        'image/svg' => ['svg'],
        'image/svg+xml' => ['svg', 'svgz'],
        'image/svg+xml-compressed' => ['svgz', 'svg.gz'],
        'image/t38' => ['t38'],
        'image/targa' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/tga' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/tiff' => ['tif', 'tiff'],
        'image/tiff-fx' => ['tfx'],
        'image/vnd.adobe.photoshop' => ['psd'],
        'image/vnd.airzip.accelerator.azv' => ['azv'],
        'image/vnd.dece.graphic' => ['uvi', 'uvvi', 'uvg', 'uvvg'],
        'image/vnd.djvu' => ['djvu', 'djv'],
        'image/vnd.djvu+multipage' => ['djvu', 'djv'],
        'image/vnd.dvb.subtitle' => ['sub'],
        'image/vnd.dwg' => ['dwg'],
        'image/vnd.dxf' => ['dxf'],
        'image/vnd.fastbidsheet' => ['fbs'],
        'image/vnd.fpx' => ['fpx'],
        'image/vnd.fst' => ['fst'],
        'image/vnd.fujixerox.edmics-mmr' => ['mmr'],
        'image/vnd.fujixerox.edmics-rlc' => ['rlc'],
        'image/vnd.microsoft.icon' => ['ico'],
        'image/vnd.mozilla.apng' => ['apng', 'png'],
        'image/vnd.ms-dds' => ['dds'],
        'image/vnd.ms-modi' => ['mdi'],
        'image/vnd.ms-photo' => ['wdp', 'jxr', 'hdp'],
        'image/vnd.net-fpx' => ['npx'],
        'image/vnd.pco.b16' => ['b16'],
        'image/vnd.rn-realpix' => ['rp'],
        'image/vnd.tencent.tap' => ['tap'],
        'image/vnd.valve.source.texture' => ['vtf'],
        'image/vnd.wap.wbmp' => ['wbmp'],
        'image/vnd.xiff' => ['xif'],
        'image/vnd.zbrush.pcx' => ['pcx'],
        'image/webp' => ['webp'],
        'image/wmf' => ['wmf'],
        'image/x-3ds' => ['3ds'],
        'image/x-adobe-dng' => ['dng'],
        'image/x-applix-graphics' => ['ag'],
        'image/x-bmp' => ['bmp', 'dib'],
        'image/x-bzeps' => ['eps.bz2', 'epsi.bz2', 'epsf.bz2'],
        'image/x-canon-cr2' => ['cr2'],
        'image/x-canon-cr3' => ['cr3'],
        'image/x-canon-crw' => ['crw'],
        'image/x-cdr' => ['cdr'],
        'image/x-cmu-raster' => ['ras'],
        'image/x-cmx' => ['cmx'],
        'image/x-compressed-xcf' => ['xcf.gz', 'xcf.bz2'],
        'image/x-dds' => ['dds'],
        'image/x-djvu' => ['djvu', 'djv'],
        'image/x-emf' => ['emf'],
        'image/x-eps' => ['eps', 'epsi', 'epsf'],
        'image/x-exr' => ['exr'],
        'image/x-fits' => ['fits', 'fit', 'fts'],
        'image/x-fpx' => ['fpx'],
        'image/x-freehand' => ['fh', 'fhc', 'fh4', 'fh5', 'fh7'],
        'image/x-fuji-raf' => ['raf'],
        'image/x-gimp-gbr' => ['gbr'],
        'image/x-gimp-gih' => ['gih'],
        'image/x-gimp-pat' => ['pat'],
        'image/x-gzeps' => ['eps.gz', 'epsi.gz', 'epsf.gz'],
        'image/x-icb' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/x-icns' => ['icns'],
        'image/x-ico' => ['ico'],
        'image/x-icon' => ['ico'],
        'image/x-iff' => ['iff', 'ilbm', 'lbm'],
        'image/x-ilbm' => ['iff', 'ilbm', 'lbm'],
        'image/x-jng' => ['jng'],
        'image/x-jp2-codestream' => ['j2c', 'j2k', 'jpc'],
        'image/x-jpeg2000-image' => ['jp2', 'jpg2'],
        'image/x-kiss-cel' => ['cel', 'kcf'],
        'image/x-kodak-dcr' => ['dcr'],
        'image/x-kodak-k25' => ['k25'],
        'image/x-kodak-kdc' => ['kdc'],
        'image/x-lwo' => ['lwo', 'lwob'],
        'image/x-lws' => ['lws'],
        'image/x-macpaint' => ['pntg'],
        'image/x-minolta-mrw' => ['mrw'],
        'image/x-mrsid-image' => ['sid'],
        'image/x-ms-bmp' => ['bmp', 'dib'],
        'image/x-msod' => ['msod'],
        'image/x-nikon-nef' => ['nef'],
        'image/x-nikon-nrw' => ['nrw'],
        'image/x-olympus-orf' => ['orf'],
        'image/x-panasonic-raw' => ['raw'],
        'image/x-panasonic-raw2' => ['rw2'],
        'image/x-panasonic-rw' => ['raw'],
        'image/x-panasonic-rw2' => ['rw2'],
        'image/x-pcx' => ['pcx'],
        'image/x-pentax-pef' => ['pef'],
        'image/x-pfm' => ['pfm'],
        'image/x-photo-cd' => ['pcd'],
        'image/x-photoshop' => ['psd'],
        'image/x-pict' => ['pic', 'pct', 'pict', 'pict1', 'pict2'],
        'image/x-portable-anymap' => ['pnm'],
        'image/x-portable-bitmap' => ['pbm'],
        'image/x-portable-graymap' => ['pgm'],
        'image/x-portable-pixmap' => ['ppm'],
        'image/x-psd' => ['psd'],
        'image/x-pxr' => ['pxr'],
        'image/x-quicktime' => ['qtif', 'qif'],
        'image/x-rgb' => ['rgb'],
        'image/x-sct' => ['sct'],
        'image/x-sgi' => ['sgi'],
        'image/x-sigma-x3f' => ['x3f'],
        'image/x-skencil' => ['sk', 'sk1'],
        'image/x-sony-arw' => ['arw'],
        'image/x-sony-sr2' => ['sr2'],
        'image/x-sony-srf' => ['srf'],
        'image/x-sun-raster' => ['sun'],
        'image/x-targa' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/x-tga' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/x-win-bitmap' => ['cur'],
        'image/x-win-metafile' => ['wmf'],
        'image/x-wmf' => ['wmf'],
        'image/x-xbitmap' => ['xbm'],
        'image/x-xcf' => ['xcf'],
        'image/x-xfig' => ['fig'],
        'image/x-xpixmap' => ['xpm'],
        'image/x-xpm' => ['xpm'],
        'image/x-xwindowdump' => ['xwd'],
        'image/x.djvu' => ['djvu', 'djv'],
    ];

    /**
     * Configuration option definitions for image rule checks.
     *
     * @return array<string, array{
     * required: bool,
     * type: string,
     * default?: mixed,
     * function?: callable,
     * validator?: callable
     * }>
     */
    public function options(): array
    {
        $options = parent::options();

        $options['minWidth'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['maxWidth'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['minHeight'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['maxHeight'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['ratio'] = [
            'required' => false,
            'type' => 'string',
            'validator' => fn(string $value) => (bool) preg_match('/^[0-9]+:[0-9]+$/', $value)
        ];

        $options['minRatio'] = [
            'required' => false,
            'type' => 'float',
            'function' => fn(string $value) => (float) $value
        ];

        $options['maxRatio'] = [
            'required' => false,
            'type' => 'float',
            'function' => fn(string $value) => (float) $value
        ];

        return $options;
    }

    /**
     * Validates an uploaded image against format, MIME, dimension, and ratio rules.
     *
     * @param mixed &$value Target data reference to validate and update.
     * @return bool `true` on success, `false` on failure.
     */
    public function validate(mixed &$value): bool
    {
        $this->errors = [];
        $this->imageInfo = [];

        // 1. Perform base file validations via parent class
        if (!parent::validate($value)) {
            return false;
        }

        // 2. Extract validated underlying file payload
        $file = $this->getValidFile();

        if (empty($file)) {
            $this->addError("Unable to read the target image file.");
            return false;
        }

        // 3. Resolve Request instance context
        $request = Request::current();

        if (!$request) {
            $this->addError("Unable to resolve current request context.");
            return false;
        }

        $uploadedFile = $request->file($this->parameter);

        if (!$uploadedFile) {
            $this->addError("No uploaded image file found in request context.");
            return false;
        }

        // 4. Validate extension against allowed format mappings
        $extension = $uploadedFile->getClientOriginalExtension();

        if (!$this->validateExtension($extension)) {
            return false;
        }

        // 5. Validate declared MIME type
        $mimeType = $uploadedFile->getClientMediaType();

        if (!$this->validateMimeType($mimeType)) {
            return false;
        }

        // 6. Verify image binary format integrity & metadata
        $path = $file['tmp_name'] ?? '';
        $imageInfo = @getimagesize($path);

        if ($imageInfo === false) {
            $this->addError("The file is not a valid or readable image.");
            return false;
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $mime = $imageInfo['mime'];

        $this->imageInfo = [
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
            'type' => $imageInfo[2] ?? null
        ];

        // 7. Validate image dimensions against set constraints
        if (!$this->validateDimensions($width, $height)) {
            return false;
        }

        // 8. Validate aspect ratio constraints
        if (!$this->validateRatio($width, $height)) {
            return false;
        }

        // 9. Attach parsed image metadata to reference payload
        $value = array_merge($file, $this->imageInfo);

        return true;
    }

    /**
     * Checks if the target extension is supported by internal MIME mappings.
     *
     * @param string $extension
     * @return bool
     */
    private function validateExtension(string $extension): bool
    {
        $allowedExtensions = array_unique(array_merge(...array_values(self::MAP)));

        if (!in_array(strtolower($extension), $allowedExtensions, true)) {
            $this->addError("The extension '{$extension}' is not permitted for images.");
            return false;
        }

        return true;
    }

    /**
     * Checks if the supplied MIME type exists within internal mappings.
     *
     * @param string $mimeType
     * @return bool
     */
    private function validateMimeType(string $mimeType): bool
    {
        if (!isset(self::MAP[$mimeType])) {
            $this->addError("The MIME type '{$mimeType}' is not supported.");
            return false;
        }

        return true;
    }

    /**
     * Validates pixel dimensions against optional min and max bounds.
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    private function validateDimensions(int $width, int $height): bool
    {
        // Width check
        if ($minWidth = $this->options['minWidth'] ?? null) {
            if ($width < $minWidth) {
                $this->addError("The image width ({$width}px) is less than the minimum required ({$minWidth}px).");
                return false;
            }
        }

        if ($maxWidth = $this->options['maxWidth'] ?? null) {
            if ($width > $maxWidth) {
                $this->addError("The image width ({$width}px) exceeds the maximum allowed ({$maxWidth}px).");
                return false;
            }
        }

        // Height check
        if ($minHeight = $this->options['minHeight'] ?? null) {
            if ($height < $minHeight) {
                $this->addError("The image height ({$height}px) is less than the minimum required ({$minHeight}px).");
                return false;
            }
        }

        if ($maxHeight = $this->options['maxHeight'] ?? null) {
            if ($height > $maxHeight) {
                $this->addError("The image height ({$height}px) exceeds the maximum allowed ({$maxHeight}px).");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates aspect ratio rules for exact match, minimum, or maximum thresholds.
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    private function validateRatio(int $width, int $height): bool
    {
        if ($height === 0) {
            $this->addError("Invalid image dimensions (height is 0).");
            return false;
        }

        $ratio = $width / $height;

        // Exact ratio matching (formatted as "width:height")
        if ($exactRatio = $this->options['ratio'] ?? null) {
            [$rWidth, $rHeight] = explode(':', $exactRatio);

            if ((float) $rHeight === 0.0) {
                $this->addError("Invalid target ratio configured.");
                return false;
            }

            $expectedRatio = (float) $rWidth / (float) $rHeight;

            if (abs($ratio - $expectedRatio) > 0.01) {
                $this->addError("The image aspect ratio ({$width}:{$height}) does not match the expected ratio ({$exactRatio}).");
                return false;
            }
        }

        // Minimum ratio check
        if ($minRatio = $this->options['minRatio'] ?? null) {
            if ($ratio < $minRatio) {
                $this->addError("The image ratio ({$ratio}) is less than the minimum allowed ratio ({$minRatio}).");
                return false;
            }
        }

        // Maximum ratio check
        if ($maxRatio = $this->options['maxRatio'] ?? null) {
            if ($ratio > $maxRatio) {
                $this->addError("The image ratio ({$ratio}) exceeds the maximum allowed ratio ({$maxRatio}).");
                return false;
            }
        }

        return true;
    }

    /**
     * Records a validation error and logs the details.
     *
     * @param string $message
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Generates human-readable primary error message, supporting template variables.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{max}', '{min}', '{width}', '{height}'],
                [
                    $this->formatSize($this->options['max'] ?? 0),
                    $this->formatSize($this->options['min'] ?? 0),
                    $this->imageInfo['width'] ?? '?',
                    $this->imageInfo['height'] ?? '?'
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The file must be a valid image.";
    }

    /**
     * Retrieves all recorded error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Gets parsed image info array containing dimensions and MIME metadata.
     *
     * @return array<string, mixed>
     */
    public function getImageInfo(): array
    {
        return $this->imageInfo;
    }

    /**
     * Retrieves the primary file extension for a given MIME type.
     *
     * @param string $mimeType
     * @return string|null
     */
    public function getExtensionFromMime(string $mimeType): ?string
    {
        return self::MAP[$mimeType][0] ?? null;
    }

    /**
     * Checks whether a specific MIME type is supported by the validator.
     *
     * @param string $mimeType
     * @return bool
     */
    public function isSupportedMime(string $mimeType): bool
    {
        return isset(self::MAP[$mimeType]);
    }
}