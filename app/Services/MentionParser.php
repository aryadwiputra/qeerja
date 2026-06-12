<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class MentionParser
{
    /**
     * Parse @mentions from comment body and resolve project members.
     *
     * @return array{mentioned_users: Collection<int, User>, mentioned_texts: list<string>}
     */
    public function parse(string $body, Project $project): array
    {
        preg_match_all('/@(\w+)/', $body, $matches);
        $mentionedTokens = array_unique($matches[1]);

        if ($mentionedTokens === []) {
            return ['mentioned_users' => collect(), 'mentioned_texts' => []];
        }

        $members = $project->members()->with('user')->get();

        $mentionedUsers = collect();
        $mentionedTexts = [];

        foreach ($mentionedTokens as $token) {
            $normalizedToken = strtolower($token);

            $matched = $members->filter(function ($member) use ($normalizedToken): bool {
                $normalizedName = strtolower(preg_replace('/[^\w]/', '', $member->user->name));

                return $normalizedName === $normalizedToken;
            });

            if ($matched->count() === 1) {
                $user = $matched->first()->user;
                $mentionedUsers->push($user);
                $mentionedTexts[] = $token;
            }
        }

        $uniqueUsers = collect();
        $uniqueTexts = [];
        $seenIds = [];

        foreach ($mentionedUsers as $i => $user) {
            if (! in_array($user->id, $seenIds, true)) {
                $uniqueUsers->push($user);
                $uniqueTexts[] = $mentionedTexts[$i];
                $seenIds[] = $user->id;
            }
        }

        return [
            'mentioned_users' => $uniqueUsers,
            'mentioned_texts' => $uniqueTexts,
        ];
    }
}
