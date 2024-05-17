<?php

declare(strict_types=1);

/**
 * Class ilOpenCastDBUpdateSteps
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ilOpenCastDBUpdateSteps implements \ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $db): void
    {
        // global $DIC;
        $this->db = $db;
        // $GLOBALS["DIC"]["ilDB"] = $this->db;
        if (!defined('SYSTEM_ROLE_ID')) {
            define('SYSTEM_ROLE_ID', '2');
        }
    }

    /**
     * Step 1
     * This step is meant to update the permissions od each xoct object according to the new added permissions set.
     */
    public function step_1(): void
    {
       /*  // Get rbac perm operation ids.
        $edit_settings_op_id = ilRbacReview::_getCustomRBACOperationId("write", $this->db);
        $upload_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_upload", $this->db);
        $edit_videos_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_edit_videos", $this->db);
        $download_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_download", $this->db);
        $record_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_record", $this->db);
        $schedule_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_schedule", $this->db);

        // Get all opencast object data
        $set = $this->db->query(
            'SELECT xoct_data.obj_id, xoct_data.streaming_only, object_reference.ref_id FROM xoct_data
            INNER JOIN object_reference ON object_reference.obj_id = xoct_data.obj_id',
        );
        while ($row = $this->db->fetchAssoc($set)) {
            $obj_id = (int) $row["obj_id"];
            $ref_id = (int) $row["ref_id"];
            $no_download = (bool) $row["streaming_only"];

            // $tree = new \ilTree($obj_id, 0, $this->db);
            // $parent_rfid = $tree->getParentId($ref_id);
            list($parent_ref_id, $parent_type) = $this->getParentCourseOrGroupData($ref_id);

            if (empty($parent_ref_id)) {
                continue;
            }
            // Admins perms.
            // $admin_role_id = $parent_obj->getDefaultAdminRole();
            if($admin_role_id = $this->getDefaultRoleIdFor('admin', $parent_ref_id, $parent_type)) {
                $admin_ops_ids = $this->getActiveOperationsOfRole($ref_id, $admin_role_id);

                if (!$no_download && !in_array($download_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $download_op_id;
                }

                if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $upload_op_id;
                    $admin_ops_ids[] = $record_op_id;
                    $admin_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $record_op_id;
                    $admin_ops_ids[] = $schedule_op_id; // ?
                } else if (in_array($edit_settings_op_id, $admin_ops_ids)) {
                    if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($edit_videos_op_id,  $admin_ops_ids)]);
                    }
                }

                $this->grantPermission($admin_role_id, $admin_ops_ids, $ref_id);
            }


            // Tutor perms.
            // $tutor_role_id = $parent_obj->getDefaultTutorRole();
            if ($tutor_role_id = $this->getDefaultRoleIdFor('tutor', $parent_ref_id, $parent_type)) {
                $tutor_ops_ids = $this->getActiveOperationsOfRole($ref_id, $tutor_role_id);

                if (!$no_download && !in_array($download_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $download_op_id;
                }

                if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $upload_op_id;
                    $tutor_ops_ids[] = $record_op_id;
                    $tutor_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $record_op_id;
                    $tutor_ops_ids[] = $schedule_op_id; // ?
                } else if (in_array($edit_settings_op_id, $tutor_ops_ids)) {
                    if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($edit_videos_op_id,  $tutor_ops_ids)]);
                    }
                }

                $this->grantPermission($tutor_role_id, $tutor_ops_ids, $ref_id);
            }

            // Member perms.
            // $member_role_id = $parent_obj->getDefaultMemberRole();
            if ($member_role_id = $this->getDefaultRoleIdFor('member', $parent_ref_id, $parent_type)) {
                $member_ops_ids = $this->getActiveOperationsOfRole($ref_id, $member_role_id);

                if (!$no_download && !in_array($download_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $download_op_id;
                }

                if (in_array($edit_videos_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $upload_op_id;
                    $member_ops_ids[] = $record_op_id;
                    $member_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $record_op_id;
                    $member_ops_ids[] = $schedule_op_id; // ?
                } else if (in_array($edit_settings_op_id, $member_ops_ids)) {
                    if (in_array($edit_videos_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($edit_videos_op_id,  $member_ops_ids)]);
                    }
                }

                $this->grantPermission($member_role_id, $member_ops_ids, $ref_id);
            }
        } */

    }

    private function grantPermission(int $role_id, array $ops, int $ref_id): void
    {
        if ($role_id == SYSTEM_ROLE_ID) {
            return;
        }

        $ops = array_map('intval', array_unique($ops));

        $ops_ids = serialize($ops);

        // Remove first.
        $query = 'DELETE FROM rbac_pa ' .
            'WHERE rol_id = %s ' .
            'AND ref_id = %s';
        $res = $this->db->queryF(
            $query,
            ['integer', 'integer'],
            [$role_id, $ref_id]
        );

        if ($ops === []) {
            return;
        }

        $query = "INSERT INTO rbac_pa (rol_id,ops_id,ref_id) " .
            "VALUES " .
            "(" . $this->db->quote($role_id, 'integer') . "," . $this->db->quote(
                $ops_ids,
                'text'
            ) . "," . $this->db->quote($ref_id, 'integer') . ")";
        $res = $this->db->manipulate($query);
    }

    private function getActiveOperationsOfRole(int $ref_id, int $role_id): array
    {
        $set = $this->db->queryF(
            "SELECT * FROM rbac_pa WHERE ref_id = %s AND rol_id = %s",
            ["integer", "integer"],
            [$ref_id, $role_id]
        );
        while ($row = $this->db->fetchAssoc($set)) {
            return unserialize($row['ops_id']);
        }
        return [];
    }

    private function getDefaultRoleIdFor(string $role_type, int $ref_id, string $obj_type): int
    {
        $role_key = "il_{$obj_type}_{$role_type}_{$ref_id}";

        $sql = 'SELECT rol_id FROM rbac_fa WHERE parent = %s';
        if ($obj_type == 'grp') {
            $sql .= ' AND assign = ' . $this->db->quote('y', 'string');
        }
        $set = $this->db->queryF(
            $sql,
            ["integer"],
            [$ref_id]
        );
        while ($row = $this->db->fetchAssoc($set)) {
            $role_id = (int) $row['rol_id'];
            $role_name = $this->lookupTitle($role_id);
            if (!strcmp($role_name, $role_key)) {
                return $role_id;
            }
        }
        return 0;
    }

    private function getParentCourseOrGroupData(int $ref_id): array
    {
        $parent_type = $this->lookupType($ref_id, true);
        while (!in_array($parent_type, ['crs', 'grp'])) {
            if ($ref_id === 1) {
                return [null, $parent_type];
            }
            $ref_id = (int) $this->getParentId($ref_id);
            $parent_type = $this->lookupType($ref_id, true);
        }

        return [$ref_id, $parent_type];
    }

    private function lookupTitle(int $id, bool $reference = false): string
    {
        $obj_id = $reference ? $this->lookupObjId($id) : $id;
        $set = $this->db->queryF(
            "SELECT title FROM object_data WHERE obj_id = %s",
            ["integer"],
            [$obj_id]
        );
        $rec = $this->db->fetchAssoc($set);
        return (string) $rec['title'];
    }

    private function lookupType(int $id, bool $reference = false): string
    {
        $obj_id = $reference ? $this->lookupObjId($id) : $id;
        $set = $this->db->queryF(
            "SELECT type FROM object_data WHERE obj_id = %s",
            ["integer"],
            [$obj_id]
        );
        $rec = $this->db->fetchAssoc($set);
        return (string) $rec['type'];
    }

    private function lookupObjId(int $ref_id): int
    {
        $set = $this->db->queryF(
            "SELECT obj_id FROM object_reference WHERE ref_id = %s",
            ["integer"],
            [$ref_id]
        );
        $rec = $this->db->fetchAssoc($set);
        return (int) $rec['obj_id'];
    }

    private function getParentId(int $child_ref_id): int
    {
        $set = $this->db->queryF(
            "SELECT parent FROM tree WHERE child = %s",
            ["integer"],
            [$child_ref_id]
        );
        $rec = $this->db->fetchAssoc($set);
        return (int) $rec['parent'];
    }

    /* private function getRBACOperationIdByName(string $name): int
    {
        $set = $this->db->queryF(
            "SELECT ops_id FROM rbac_operations WHERE operation = %s",
            ["string"],
            [$name]
        );
        $rec = $this->db->fetchAssoc($set);
        return (int) $rec['ops_id'];
    } */
}
