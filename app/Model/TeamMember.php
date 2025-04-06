<?php
namespace WorkSpace\Model;

class TeamMember extends Entity
{
   protected $table = 'TEAM_MEMBERS';
   protected $primaryKey = 'IDTeamMember';
   protected $fillable = ['IDTeam', 'IDUser', 'RoleInTeam', 'JoinAt', 'IsDeleted'];
   protected $attributes = [
      'RoleInTeam' => 'Member',
      // 'JoinAt' => 'CURRENT_TIMESTAMP'
   ];

   public function team()
   {
      return $this->belongsTo(Team::class, 'IDTeam', 'IDTeam');
   }

   public function user()
   {
      return $this->belongsTo(User::class, 'IDUser', 'IDUser');
   }
}