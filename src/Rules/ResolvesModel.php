<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Foundation\Filesystem\DirectoryScanner;

/**
 * Trait ResolvesModelClass
 *
 * Résout un nom de modèle transformé (ex: 'employee' -> 'Employee') vers sa
 * classe pleinement qualifiée dans App\Models, avec mise en cache pour éviter
 * de rescanner le filesystem à chaque validation. Accepte aussi un nom de
 * classe pleinement qualifié direct via l'option 'class', qui court-circuite
 * le scan.
 *
 * Utilisé par les Rule de validation qui ont besoin de résoudre un modèle
 * à partir de son nom (IDValidator, EmailValidator, ...).
 */
trait ResolvesModel
{
    /**
     * Cache des classes de modèle déjà résolues (nom transformé => classe
     * pleinement qualifiée), partagé entre toutes les Rule qui utilisent ce trait.
     *
     * @var array<string, string>
     */
    private static array $resolvedModels = [];

    /**
     * Résout le nom de classe pleinement qualifié du modèle ciblé.
     *
     * @param string $modelName Nom du modèle déjà transformé en StudlyCase.
     * @param ?string $explicitClass Classe pleinement qualifiée fournie directement
     *   (ex: via une option 'class'), qui court-circuite le scan si présente.
     * @throws \Exception Si aucun modèle correspondant n'est trouvé.
     * @return string
     */
    private function resolveModelClass(string $modelName, ?string $explicitClass = null) : string
    {
        if ($explicitClass) {
            return $explicitClass;
        }

        if (isset(self::$resolvedModels[$modelName])) {
            return self::$resolvedModels[$modelName];
        }

        $scanner = new DirectoryScanner(
            rootPath: app()->appPath('Models'),
            baseNamespace: "App\\Models",
        );

        $matches = $scanner->discoverClasses(
            fn(string $className) => class_basename($className) === $modelName
        );

        if (count($matches) === 0) {
            throw new \Exception("Model {$modelName} not found in App\\Models namespace.");
        }

        return self::$resolvedModels[$modelName] = $matches[0];
    }
}