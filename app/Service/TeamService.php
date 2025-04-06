<?php
namespace WorkSpace\Service;

use Exception;
use WorkSpace\Model\Team;
use WorkSpace\Model\TeamMember;

class TeamService
{
    protected $teamModel;
    protected $teamMemberModel;

    public function __construct(Team $teamModel, TeamMember $teamMemberModel)
    {
        $this->teamModel = $teamModel;
        $this->teamMemberModel = $teamMemberModel;
    }

    public function getAllTeams($IDUser)
    {
        $teamIds = $this->teamMemberModel
            ->where([
                ['IDUser', $IDUser],
                ['IsDeleted', false]
            ])
            ->pluck('IDTeam');

        $teams = Team::with('leader')
            ->where('IsDeleted', 0)
            ->whereIn('IDTeam', $teamIds)
            ->get();

        // Ẩn cột IDLeader khỏi response
        $teams->makeHidden('IDLeader');

        return $teams;
    }

    // Tạo một team mới
    public function createTeam($data, $IDLeader)
    {
        $requiredFields = [
            'teamName' => 'Team Name is required'
        ];

        foreach ($requiredFields as $field => $message) {
            if (empty($data[$field])) {
                throw new Exception($message);
            }
        }

        $team = $this->findTeam('TeamName', $data['teamName'], $IDLeader);
        if ($team) {
            throw new Exception('Team already exists');
        }

        try {
            // Tạo team mới
            $team = new Team([
                "IDLeader" => $IDLeader,
                "TeamName" => $data["teamName"],
                "TeamSize" => 1,
                "TeamDescription" => $data["teamDescription"] ?? null,
            ]);
            
            // Lưu team để lấy IDTeam
            $team->save();
            $team = $this->findTeam('TeamName', $data['teamName'], $IDLeader);

            // Thêm leader vào team_members sử dụng TeamMemberService
            $this->teamMemberModel->create([
                "IDTeam" => $team->IDTeam,
                "IDUser" => $IDLeader,
                "RoleInTeam" => 'Leader'
            ]);


            return $team;
        } catch (Exception $e) {
            throw new Exception("Failed to create team: " . $e->getMessage());
        }
    }

   // Tìm một team theo trường cụ thể
   public function findTeam($field, $value, $IDLeader)
   {
      return Team::where($field, $value)
         ->where('IDLeader', $IDLeader)
         ->where('IsDeleted', false)
         ->first();
   }
  
   public function leaveTeam($IDUser, $IDTeam)
   {
       // Tìm bản ghi TeamMember dựa trên IDUser và IDTeam
       $teamMember = $this->teamMemberModel
           ->where([
               ['IDUser', $IDUser],
               ['IDTeam', $IDTeam],
               ['IsDeleted', false]
           ])
           ->first();

       if (!$teamMember) {
           throw new Exception('You are not a member of this team or the team does not exist');
       }

       // Kiểm tra xem team có tồn tại và user có phải leader không
       $team = $this->teamModel
           ->where('IDTeam', $IDTeam)
           ->where('IsDeleted', 0)
           ->first();

       if (!$team) {
           throw new Exception('Team does not exist');
       }

       if ($team->IDLeader == $IDUser) {
           throw new Exception('Team leader cannot leave the team');
       }

       // Đánh dấu IsDeleted = true để rời team
       $teamMember->IsDeleted = true;
       $teamMember->save();

       return true;
   }

   public function deleteTeam($IDTeam, $IDUser)
   {
       // Kiểm tra team có tồn tại không
       $team = $this->teamModel
           ->where('IDTeam', $IDTeam)
           ->where('IsDeleted', 0)
           ->first();

       if (!$team) {
           throw new Exception('Team does not exist');
       }

       // Kiểm tra quyền leader
       if ($team->IDLeader != $IDUser) {
           throw new Exception('Only team leader can delete the team');
       }

       try {
           // Đánh dấu xóa mềm team
           $team->IsDeleted = true;
           $team->save();

           // Đánh dấu xóa mềm tất cả thành viên trong team
           $this->teamMemberModel
               ->where('IDTeam', $IDTeam)
               ->where('IsDeleted', false)
               ->update(['IsDeleted' => true]);

           return true;
       } catch (Exception $e) {
           throw new Exception("Failed to delete team: " . $e->getMessage());
       }
   }

}