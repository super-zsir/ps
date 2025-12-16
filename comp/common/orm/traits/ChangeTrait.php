<?php

namespace Imee\Comp\Common\Orm\Traits;

trait ChangeTrait
{
    public function getChange()
    {
        $changes = [];
        if ($this->hasSnapshotData()) {
            $oldSnapshotData = $this->getOldSnapshotData();
            $changedFields = $this->getChangedFields();
            if (!empty($changedFields)) {
                foreach ($changedFields as $changedField) {
                    if (!array_key_exists($changedField, $oldSnapshotData)) {
                        $changes[$changedField] = [null, $this->$changedField];
                    } else {
                        $changes[$changedField] = [$oldSnapshotData[$changedField], $this->$changedField];
                    }
                }
            }
        } else {
            $attributes = $this->getModelsMetaData()->getAttributes($this);
            foreach ($attributes as $attribute) {
                if (property_exists($this, $attribute) && !is_null($this->$attribute)) {
                    $changes[$attribute] = [null, $this->$attribute];
                }
            }
        }
        return $changes;
    }
}
