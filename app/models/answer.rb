# == Schema Information
#
# Table name: answers
#
#  id          :integer          not null, primary key
#  text        :string(140)      not null
#  anonymous   :boolean          default(FALSE), not null
#  created_at  :datetime         not null
#  question_id :integer          not null
#  person_id   :integer          not null
#

class Answer < ActiveRecord::Base
  belongs_to :question
  belongs_to :person

  validates :text, :anonymous, :created_at, presence: true
  validates :text, length: {maximum: 140}
end
