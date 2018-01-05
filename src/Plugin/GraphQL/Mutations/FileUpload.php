<?php

namespace Drupal\graphql_upload\Plugin\GraphQL\Mutations;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Youshido\GraphQL\Execution\ResolveInfo;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

/**
 * A sample file upload mutation.
 *
 * @GraphQLMutation(
 *   id = "file_upload",
 *   secure = "false",
 *   name = "fileUpload",
 *   type = "File",
 *   entity_type = "file",
 *   entity_bundle = "file",
 *   arguments = {
 *     "input" = "FileUploadInput"
 *   }
 * )
 */
class FileUpload extends CreateEntityBase {

  /**
   * The Upload Handler.
   *
   * @var \Drupal\graphql_file_upload\UploadHandlerInterface
   */
  protected $uploadHandler;

  /**
   * The Upload Save.
   *
   * @var \Drupal\graphql_file_upload\GraphQLUploadSaveInterface
   */
  protected $uploadSave;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The plugin implemented entityTypeManager
   * @param \Drupal\graphql_file_upload\UploadHandler $uploadHandler
   *   The upload Handler
   * @param \Drupal\graphql_file_upload\GraphQLUploadSave
   *   The upload save
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, $uploadHandler, $uploadSave) {
    $this->entityTypeManager = $entityTypeManager;
    $this->uploadHandler = $uploadHandler;
    $this->uploadSave = $uploadSave;
    $this->currentUser = \Drupal::currentUser();
    parent::__construct($configuration, $pluginId, $pluginDefinition, $entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('graphql_upload.upload_handler'),
      $container->get('graphql_upload.upload_save')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {
    $file = $args['input'];


    $data = file_get_contents($file);
    if($data == NULL){
      return NULL;
    }

    $destination = file_default_scheme() . '://graphql-upload-files/' . $file->getClientOriginalName();
    $directory = file_stream_wrapper_uri_normalize(dirname($destination));

    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      throw new \Exception('Could not created destination directory.');
    }

    $entity = file_save_data($data, $destination);

    if (!$entity) {
      throw new \Exception('Could not upload file.');
    }

    $entity->setTemporary();
    $entity->save();

    return $entity;
  }


  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput(array $inputArgs, InputObjectType $inputType, ResolveInfo $info) {
    return [
      'name' => $inputArgs['name']
    ];
  }

}
