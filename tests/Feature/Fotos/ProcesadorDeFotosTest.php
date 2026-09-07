<?php

namespace Tests\Feature\Fotos;

use App\Fotos\DecodificadorHeic;
use App\Fotos\Foto;
use App\Fotos\FotoDemasiadoPesada;
use App\Fotos\FotoNoSoportada;
use App\Fotos\ProcesadorDeFotos;
use App\Fotos\Variante;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FotosDeEjemplo;
use Tests\TestCase;

class ProcesadorDeFotosTest extends TestCase
{
    private Filesystem $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = Storage::fake('public');
    }

    public function test_una_foto_de_ocho_megas_queda_debajo_de_cuatrocientos_kilobytes_en_su_version_grande(): void
    {
        $origen = FotosDeEjemplo::comoDeCelular();

        $this->assertGreaterThan(8 * 1024 * 1024, filesize($origen), 'La foto de ejemplo tiene que pesar más de 8 MB.');

        $foto = (new ProcesadorDeFotos)->procesar($origen);

        foreach (['webp', 'jpg'] as $extension) {
            $ruta = $foto->ruta(Variante::Grande, $extension);

            $this->assertLessThanOrEqual(
                400 * 1024,
                strlen($this->disco->get($ruta)),
                "La versión grande en {$extension} pasa los 400 KB.",
            );
        }

        $this->assertSame([1600, 1200], $this->medidas($foto, Variante::Grande));
    }

    public function test_una_foto_acostada_por_el_exif_se_guarda_derecha(): void
    {
        $foto = (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::acostada(6));

        // Los píxeles vienen en 1200 × 800; la orientación 6 la para de pie.
        $this->assertSame([800, 1200], $this->medidas($foto, Variante::Grande));
        $this->assertSame([320, 480], $this->medidas($foto, Variante::Miniatura));
    }

    public function test_una_foto_derecha_se_deja_como_esta(): void
    {
        $foto = (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::acostada(1));

        $this->assertSame([1200, 800], $this->medidas($foto, Variante::Grande));
    }

    public function test_un_archivo_que_pasa_el_limite_se_rechaza_antes_de_procesar(): void
    {
        $pesado = UploadedFile::fake()->create('par.jpg', 13 * 1024);

        try {
            (new ProcesadorDeFotos)->procesar($pesado);
            $this->fail('Un archivo de 13 MB tenía que rechazarse.');
        } catch (FotoDemasiadoPesada $error) {
            $this->assertStringContainsString('13 MB', $error->getMessage());
            $this->assertStringContainsString('El límite es 12 MB por archivo.', $error->getMessage());
        }

        $this->assertSame([], $this->disco->allFiles());
    }

    public function test_ninguna_imagen_procesada_conserva_datos_de_ubicacion(): void
    {
        $origen = FotosDeEjemplo::conUbicacion();

        $this->assertArrayHasKey('GPSLatitude', (array) @exif_read_data($origen), 'La foto de ejemplo tenía que traer ubicación.');

        $foto = (new ProcesadorDeFotos)->procesar($origen);

        foreach ($foto->archivos() as $ruta) {
            $bytes = $this->disco->get($ruta);

            $this->assertStringNotContainsString("Exif\x00\x00", $bytes, "El archivo [{$ruta}] conserva el bloque EXIF.");

            $metadatos = (array) @exif_read_data($this->disco->path($ruta));

            $this->assertSame(
                [],
                preg_grep('/^GPS/', array_keys($metadatos)),
                "El archivo [{$ruta}] conserva datos de ubicación.",
            );
            $this->assertStringNotContainsString(
                'EXIF',
                (string) ($metadatos['SectionsFound'] ?? ''),
                "El archivo [{$ruta}] conserva metadatos de la cámara.",
            );
        }
    }

    public function test_cada_foto_deja_las_cuatro_versiones_bajo_el_ano_y_el_mes(): void
    {
        Carbon::setTestNow('2026-09-06 12:00:00');

        $foto = (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::acostada(1));

        $this->assertStringStartsWith('trabajos/2026/09/', $foto->base);

        $esperados = [
            $foto->base.'-grande.webp',
            $foto->base.'-grande.jpg',
            $foto->base.'-miniatura.webp',
            $foto->base.'-miniatura.jpg',
        ];

        foreach ($esperados as $ruta) {
            $this->disco->assertExists($ruta);
        }

        $this->assertCount(4, $this->disco->allFiles());
        $this->assertEqualsCanonicalizing($esperados, $foto->archivos());
    }

    public function test_la_miniatura_pesa_menos_que_la_grande(): void
    {
        $foto = (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::comoDeCelular());

        $this->assertLessThan(
            strlen($this->disco->get($foto->ruta(Variante::Grande, 'webp'))),
            strlen($this->disco->get($foto->ruta(Variante::Miniatura, 'webp'))),
        );
    }

    public function test_una_foto_mas_chica_que_la_variante_no_se_agranda(): void
    {
        $foto = (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::chica());

        $this->assertSame([320, 240], $this->medidas($foto, Variante::Grande));
        $this->assertSame([320, 240], $this->medidas($foto, Variante::Miniatura));
    }

    public function test_un_archivo_que_no_es_foto_dice_que_formatos_se_aceptan(): void
    {
        $this->expectException(FotoNoSoportada::class);
        $this->expectExceptionMessage('Se aceptan '.$this->formatosEnPalabras());

        (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::queNoEsFoto());
    }

    public function test_una_foto_heic_dice_que_formatos_acepta_el_servidor_cuando_no_puede_abrirla(): void
    {
        if ((new DecodificadorHeic)->disponible()) {
            $this->markTestSkipped('Este servidor sí puede abrir HEIC.');
        }

        $this->expectException(FotoNoSoportada::class);
        $this->expectExceptionMessage('Este servidor no puede abrir fotos HEIC. Se aceptan '.$this->formatosEnPalabras());

        (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::heic());
    }

    public function test_las_reglas_de_validacion_cortan_en_doce_megas(): void
    {
        $reglas = (new ProcesadorDeFotos)->reglasDeValidacion();

        $this->assertContains('max:12288', $reglas);
        $this->assertContains('file', $reglas);
    }

    /**
     * @return array{int, int}
     */
    private function medidas(Foto $foto, Variante $variante): array
    {
        $medidas = getimagesize($this->disco->path($foto->ruta($variante, 'jpg')));

        $this->assertIsArray($medidas);

        return [$medidas[0], $medidas[1]];
    }

    private function formatosEnPalabras(): string
    {
        $formatos = (new ProcesadorDeFotos)->formatosAceptados();
        $ultimo = array_pop($formatos);

        return implode(', ', $formatos).' y '.$ultimo;
    }
}
