<?php

namespace Database\Seeders;

use App\Models\Doc;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Meeting Notes',
                'slug' => 'meeting-notes',
                'content' => '<h2>Meeting Notes</h2><p><strong>Date:</strong> <br><strong>Attendees:</strong> <br><strong>Agenda:</strong> </p><hr><h3>Discussion</h3><p></p><h3>Action Items</h3><ul><li><p> </p></li><li><p> </p></li></ul><h3>Next Steps</h3><p></p>',
                'sort_order' => 10,
            ],
            [
                'title' => 'Technical Specification',
                'slug' => 'technical-specification',
                'content' => '<h2>Technical Specification</h2><p><strong>Project:</strong> <br><strong>Author:</strong> <br><strong>Date:</strong> </p><hr><h3>Overview</h3><p></p><h3>Architecture</h3><p></p><h3>Data Model</h3><p></p><h3>API Endpoints</h3><p></p><h3>Tech Stack</h3><ul><li><p> </p></li><li><p> </p></li></ul>',
                'sort_order' => 20,
            ],
            [
                'title' => 'Standard Operating Procedure',
                'slug' => 'standard-operating-procedure',
                'content' => '<h2>Standard Operating Procedure</h2><p><strong>Title:</strong> <br><strong>Department:</strong> <br><strong>Effective Date:</strong> </p><hr><h3>Purpose</h3><p></p><h3>Scope</h3><p></p><h3>Procedure</h3><ol><li><p> </p></li><li><p> </p></li><li><p> </p></li></ol><h3>References</h3><p></p>',
                'sort_order' => 30,
            ],
        ];

        $superAdmin = User::where('is_super_admin', true)->first();

        if (! $superAdmin) {
            return;
        }

        foreach (Project::all() as $project) {
            foreach ($templates as $tpl) {
                Doc::firstOrCreate(
                    ['project_id' => $project->id, 'slug' => $tpl['slug']],
                    [
                        'project_id' => $project->id,
                        'parent_id' => null,
                        'created_by' => $superAdmin->id,
                        'title' => $tpl['title'],
                        'slug' => $tpl['slug'],
                        'content' => $tpl['content'],
                        'sort_order' => $tpl['sort_order'],
                    ],
                );
            }
        }
    }
}
