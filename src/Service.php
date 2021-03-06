<?php
namespace spicyweb\fieldlabels;

use yii\base\Component;
use yii\base\Exception;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use spicyweb\fieldlabels\Event as FieldLabelsEvent;
use spicyweb\fieldlabels\models\FieldLabel as FieldLabelModel;
use spicyweb\fieldlabels\records\FieldLabel as FieldLabelRecord;

/**
 * Class Service
 *
 * @package spicyweb\fieldlabels
 * @author Spicy Web <craft@spicyweb.com.au>
 * @author Benjamin Fleming
 * @since 1.0.0
 */
class Service extends Component
{
    /**
     * Returns all of Field Labels' labels.
     *
     * @return array
     */
    public function getAllLabels(): array
    {
        $labels = [];
        $labelsQuery = $this->_createQuery()
            ->orderBy(['id' => SORT_DESC]);

        foreach ($labelsQuery->all() as $label) {
            $labels[] = new FieldLabelModel($label);
        }

        return $labels;
    }

    /**
     * Returns all labels associated with the given field layout ID.
     *
     * @param int $fieldLayoutId
     * @return array
     */
    public function getLabels(int $fieldLayoutId): array
    {
        $labels = [];
        $labelsQuery = $this->_createQuery()
            ->where(['fieldLayoutId' => $fieldLayoutId])
            ->orderBy(['id' => SORT_DESC]);

        foreach ($labelsQuery->all() as $label) {
            $labels[] = new FieldLabelModel($label);
        }

        return $labels;
    }

    /**
     * Saves all labels for the given field layout ID.
     *
     * @param array $labels The `FieldLabelModel`s to save
     * @param int $layoutId
     */
    public function saveLabels(array $labels, int $layoutId)
    {
        foreach ($labels as $fieldId => $labelInfo) {
            $label = new FieldLabelModel();
            $label->fieldId = $fieldId;
            $label->fieldLayoutId = $layoutId;

            if (array_key_exists('name', $labelInfo)) {
                $label->name = $labelInfo['name'];
            }

            if (array_key_exists('instructions', $labelInfo)) {
                $label->instructions = $labelInfo['instructions'];
            }

            $this->saveLabel($label);
        }
    }

    /**
     * Saves a field label.
     *
     * @param FieldLabelModel $label
     * @throws \Throwable
     */
    public function saveLabel(FieldLabelModel $label)
    {
        $fieldsService = Craft::$app->getFields();
        $projectConfigService = Craft::$app->getProjectConfig();
        $fieldId = $label->fieldId;
        $fieldLayoutId = $label->fieldLayoutId;
        $isNew = false;

        if ($label->id === null) {
            $result = $this->_createQuery()
                ->where([
                    'fieldId' => $fieldId,
                    'fieldLayoutId' => $fieldLayoutId,
                ])
                ->one();

            if ($result) {
                $label->id = (int)$result['id'];
                $label->uid = $result['uid'];
            } else {
                $isNew = true;
            }
        }

        $field = $fieldsService->getFieldById($fieldId);

        // Can't do getLayoutById() because it'll have the old UID for users, Neo fields, Commerce orders/subscriptions
        // $layout = $fieldsService->getLayoutById($fieldLayoutId);
        $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayoutId);

        if (!$field) {
            throw new Exception(Craft::t('fieldlabels', 'No field exists with the ID {id}.', ['id' => $fieldId]));
        }

        if (!$layoutUid) {
            throw new Exception(Craft::t('fieldlabels', 'No field layout exists with the ID {id}.', ['id' => $fieldLayoutId]));
        }

        if (!$label->validate()) {
            return false;
        }

        // Trigger a `beforeSaveLabel` event
        if ($this->hasEventHandlers('beforeSaveLabel')) {
            $this->trigger('beforeSaveLabel', new FieldLabelsEvent([
                'label'      => $label,
                'isNewLabel' => $isNew,
            ]));
        }

        $data = [
            'field' => $field->uid,
            'fieldLayout' => $layoutUid,
            'name' => $label->name,
            'instructions' => $label->instructions,
        ];

        if ($isNew) {
            $label->uid = StringHelper::UUID();
        } else if (!$label->uid) {
            $label->uid = Db::uidById('{{%fieldlabels}}', $label->id);
        }

        $projectConfigService->set('fieldlabels.' . $label->uid, $data);

        if ($isNew) {
            $label->id = Db::idByUid('{{%fieldlabels}}', $label->uid);
        }

        return true;
    }

    /**
     * Handles a field label change.
     *
     * @param ConfigEvent $event
     * @throws \Throwable
     */
    public function handleChangedLabel(ConfigEvent $event)
    {
        $fieldsService = Craft::$app->getFields();
        $projectConfigService = Craft::$app->getProjectConfig();
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;
        $fieldId = Db::idByUid('{{%fields}}', $data['field']);
        $fieldLayoutId = Db::idByUid('{{%fieldlayouts}}', $data['fieldLayout']);

        // Make sure the field and field layout have been synced
        if ($fieldId === null || $fieldLayoutId === null) {
            $projectConfigService->defer($event, [$this, __FUNCTION__]);
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $record = FieldLabelRecord::findOne(['uid' => $uid]);
            $isNew = false;

            if ($record === null) {
                $record = new FieldLabelRecord();
                $isNew = true;
            }

            $record->fieldId = $fieldId;
            $record->fieldLayoutId = $fieldLayoutId;
            $record->name = $data['name'];
            $record->instructions = $data['instructions'];
            $record->uid = $uid;
            $record->save(false);

            $transaction->commit();

            // Trigger an `afterSaveLabel` event
            if ($this->hasEventHandlers('afterSaveLabel')) {
                $this->trigger('afterSaveLabel', new FieldLabelsEvent([
                    'label'      => $label,
                    'isNewLabel' => $isNew,
                ]));
            }
        } catch(\Throwable $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    private function _createQuery()
    {
        return (new Query)
            ->select([
                'id',
                'fieldId',
                'fieldLayoutId',
                'name',
                'instructions',
                'uid',
            ])
            ->from('{{%fieldlabels}}');
    }
}
