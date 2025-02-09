<?php
namespace Clicalmani\Validation\Validators;

class ImageValidator extends FileValidator
{
    protected string $argument = 'image';

    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $is_file = parent::validate($value, $options);

        if (TRUE === $is_file) {
            /** @var \Clicalmani\Http\Request */
            $request = \Clicalmani\Http\Request::currentRequest();
            $file = $request->file($this->parameter);

            return in_array(
                $file->getClientOriginalExtension(), 
                array_merge( ...array_values(self::MAP) )
            );
        }

        return false;
    }

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
}
