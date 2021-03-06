# == Schema Information
#
# Table name: presentations
#
#  id             :integer          not null, primary key
#  subject        :string(100)      not null
#  status         :integer          default(0), not null
#  created_at     :datetime         not null
#  instruction_id :integer          not null
#  person_id      :integer          not null
#

class Presentation < ApplicationRecord
  SUBJECT_MAX_LENGTH = 100

  belongs_to :instruction
  belongs_to :person
  has_many :questions
  has_many :requests
  has_many :materials, as: :attachable
  has_many :clusters
  has_many :presentation_faq

  validates :subject, :status, presence: true
  validates :subject, length: {maximum: self::SUBJECT_MAX_LENGTH}
end
