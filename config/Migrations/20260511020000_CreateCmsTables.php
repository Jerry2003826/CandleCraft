<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCmsTables extends BaseMigration
{
    public function change(): void
    {
        $this->createSitePages();
        $this->createSiteMedia();
        $this->createPageSections();
        $this->createPageSectionRevisions();
        $this->createPageSectionLocks();
    }

    private function bigPk(string $tableName): \Migrations\Db\Table
    {
        return $this->table($tableName, [
            'id' => false,
            'primary_key' => ['id'],
        ])->addColumn('id', 'biginteger', [
            'identity' => true,
            'signed' => false,
            'null' => false,
        ]);
    }

    private function createSitePages(): void
    {
        if ($this->hasTable('site_pages')) {
            return;
        }
        $table = $this->bigPk('site_pages');
        $table->addColumn('page_slug', 'string', ['limit' => 80, 'null' => false]);
        $table->addColumn('page_title', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('is_active', 'boolean', ['default' => true, 'null' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0, 'null' => false]);
        $table->addColumn('created_at', 'datetime', ['null' => false]);
        $table->addColumn('updated_at', 'datetime', ['null' => false]);
        $table->addIndex(['page_slug'], ['unique' => true, 'name' => 'uq_site_pages_slug']);
        $table->create();
    }

    private function createSiteMedia(): void
    {
        if ($this->hasTable('site_media')) {
            return;
        }
        $table = $this->bigPk('site_media');
        $table->addColumn('file_name', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('file_path', 'string', ['limit' => 500, 'null' => false]);
        $table->addColumn('file_url', 'string', ['limit' => 500, 'null' => false]);
        $table->addColumn('mime_type', 'string', ['limit' => 100, 'null' => false]);
        $table->addColumn('file_size', 'integer', ['null' => false, 'signed' => false]);
        $table->addColumn('alt_text', 'string', ['limit' => 500, 'null' => true, 'default' => null]);
        $table->addColumn('uploaded_by_id', 'biginteger', [
            'null' => true, 'default' => null, 'signed' => false,
        ]);
        $table->addColumn('uploaded_at', 'datetime', ['null' => false]);
        $table->addColumn('updated_at', 'datetime', ['null' => false]);
        $table->addIndex(['uploaded_by_id'], ['name' => 'idx_site_media_uploader']);
        $table->addForeignKey('uploaded_by_id', 'users', 'user_id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }

    private function createPageSections(): void
    {
        if ($this->hasTable('page_sections')) {
            return;
        }
        $table = $this->bigPk('page_sections');
        $table->addColumn('page_id', 'biginteger', ['null' => false, 'signed' => false]);
        $table->addColumn('section_key', 'string', ['limit' => 120, 'null' => false]);
        $table->addColumn('section_label', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('section_hint', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('content_type', 'string', ['limit' => 20, 'null' => false]);
        $table->addColumn('content_value', 'text', [
            'limit' => 4294967295,
            'null' => true, 'default' => null,
        ]);
        $table->addColumn('media_id', 'biginteger', [
            'null' => true, 'default' => null, 'signed' => false,
        ]);
        $table->addColumn('is_active', 'boolean', ['default' => true, 'null' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0, 'null' => false]);
        $table->addColumn('updated_at', 'datetime', ['null' => false]);
        $table->addColumn('updated_by_id', 'biginteger', [
            'null' => true, 'default' => null, 'signed' => false,
        ]);
        $table->addIndex(['page_id', 'section_key'], [
            'unique' => true, 'name' => 'uq_page_sections_key',
        ]);
        $table->addIndex(['page_id', 'sort_order'], ['name' => 'idx_page_sections_order']);
        $table->addIndex(['media_id'], ['name' => 'idx_page_sections_media']);
        $table->addForeignKey('page_id', 'site_pages', 'id', [
            'delete' => 'CASCADE', 'update' => 'CASCADE',
        ]);
        $table->addForeignKey('media_id', 'site_media', 'id', [
            'delete' => 'SET_NULL', 'update' => 'CASCADE',
        ]);
        $table->addForeignKey('updated_by_id', 'users', 'user_id', [
            'delete' => 'SET_NULL', 'update' => 'CASCADE',
        ]);
        $table->create();
    }

    private function createPageSectionRevisions(): void
    {
        if ($this->hasTable('page_section_revisions')) {
            return;
        }
        $table = $this->bigPk('page_section_revisions');
        $table->addColumn('section_id', 'biginteger', ['null' => false, 'signed' => false]);
        $table->addColumn('content_value_snapshot', 'text', [
            'limit' => 4294967295,
            'null' => true, 'default' => null,
        ]);
        $table->addColumn('media_id_snapshot', 'biginteger', [
            'null' => true, 'default' => null, 'signed' => false,
        ]);
        $table->addColumn('changed_by_id', 'biginteger', [
            'null' => true, 'default' => null, 'signed' => false,
        ]);
        $table->addColumn('changed_at', 'datetime', ['null' => false]);
        $table->addColumn('change_summary', 'string', [
            'limit' => 500, 'null' => true, 'default' => null,
        ]);
        $table->addIndex(['section_id', 'changed_at'], ['name' => 'idx_revisions_section_time']);
        $table->addForeignKey('section_id', 'page_sections', 'id', [
            'delete' => 'CASCADE', 'update' => 'CASCADE',
        ]);
        $table->addForeignKey('changed_by_id', 'users', 'user_id', [
            'delete' => 'SET_NULL', 'update' => 'CASCADE',
        ]);
        $table->create();
    }

    private function createPageSectionLocks(): void
    {
        if ($this->hasTable('page_section_locks')) {
            return;
        }
        $table = $this->bigPk('page_section_locks');
        $table->addColumn('section_id', 'biginteger', ['null' => false, 'signed' => false]);
        $table->addColumn('locked_by_id', 'biginteger', ['null' => false, 'signed' => false]);
        $table->addColumn('locked_at', 'datetime', ['null' => false]);
        $table->addColumn('expires_at', 'datetime', ['null' => false]);
        $table->addIndex(['section_id'], ['unique' => true, 'name' => 'uq_section_locks_section']);
        $table->addIndex(['expires_at'], ['name' => 'idx_section_locks_expires']);
        $table->addForeignKey('section_id', 'page_sections', 'id', [
            'delete' => 'CASCADE', 'update' => 'CASCADE',
        ]);
        $table->addForeignKey('locked_by_id', 'users', 'user_id', [
            'delete' => 'CASCADE', 'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
